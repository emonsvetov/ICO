<?php
namespace App\Services;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Traits\IdExtractor;
use App\Models\Traits\Filterable;
use App\Models\JournalEventType;
use App\Services\InvoiceService;
use App\Services\UserService;
use App\Models\Account;
use App\Models\Program;
use App\Models\Posting;
use App\Models\Role;
use App\Models\User;
use DB;

class ProgramService
{
    use IdExtractor;
    public $program;
    public $program_account_holder_id;
    public $user_account_holder_id;

    public function __construct(UserService $userService)  {
        $this->userService = $userService;
    }

    const DEFAULT_PARAMS = [
        'status' => '',
        'keyword' => '',
        'sortby' => 'id',
        'direction' => 'asc',
        'minimal' => false, // will return id and name with full list
        'paginate' => true,
        'tree' => true, //whether to return data in tree format
        'flatlist' => false, //whether to return data in tree format
        'except' => [], //array of primary keys
    ];

    private function _buildParams( $override = [] ) {
        // pr($override);
        $params = [];
        $status = !empty($override['status']) ? $override['status'] : request()->get('status', '');
        $keyword = !empty($override['keyword']) ? $override['keyword'] : request()->get('keyword', '');
        $sortby = !empty($override['sortby']) ? $override['sortby'] : request()->get('sortby', 'id');
        $direction = !empty($override['direction']) ? $override['direction'] : request()->get('direction', 'asc');
        $tree = isset($override['tree']) ? $override['tree'] : request()->get('tree', true);
        $minimal = !empty($override['minimal']) ? $override['minimal'] : request()->get('minimal', false);
        $flatlist = !empty($override['flatlist']) ? $override['flatlist'] : request()->get('flatlist', false);
        $except = !empty($override['except']) ? $override['except'] : request()->get('except', '');
        $limit = !empty($override['limit']) ? $override['limit'] : request()->get('limit', 10);
        $paginate = !empty($override['paginate']) ? $override['paginate'] : request()->get('paginate', true);
        $params['status'] = $status;
        $params['keyword'] = $keyword;
        $params['sortby'] = $sortby;
        $params['direction'] = $direction;
        $params['tree'] = $tree;
        $params['minimal'] = $minimal;
        $params['limit'] = $limit;
        $params['paginate'] = $paginate;
        $params['flatlist'] = $flatlist;
        $params['except'] = $except;
        // pr($params);
        return $params;
    }

    private function _buildQuery( $organization, $params = [] )   {

        $params = array_merge(self::DEFAULT_PARAMS, self::_buildParams($params));

        extract($params);

        $where = [];

        if( $status )
        {
            $where[] = ['status', $status];
        }

        if( $sortby == "name" )
        {
            $collation =  "COLLATE utf8mb4_unicode_ci"; //COLLATION is required to support case insensitive ordering
            $orderByRaw = "{$sortby} {$collation} {$direction}";
        }
        else
        {
            $orderByRaw = "{$sortby} {$direction}";
        }

        $query = Program::where($where);

        if($status && strtolower($status) == 'deleted')     {
            $query = $query->withTrashed();
        }

        if( $keyword )
        {
            $query = $query->where(function($query1) use($keyword) {
                $query1->orWhere('id', 'LIKE', "%{$keyword}%")
                ->orWhere('name', 'LIKE', "%{$keyword}%");
            });
        }

        $notIn = '';

        if( $except )   {
            if(is_array($except))   {
                $notIn = $except;
            } elseif(strpos($except, ',')) {
                $notIn = explode(trim($except));
            }   elseif( (int) $except ) {
                $notIn = [$except];
            }
            if( $notIn )    {
                $query = $query->whereNotIn('id', $notIn);
            }
        }

        if( $minimal )  {
            $query = $query->select('id', 'name');
        }

        if( $tree )    {
            if( $minimal )  {
                $query = $query->with(['children' => function($query) use($notIn)  {
                        $subquery = $query->select('id','name','parent_id');
                        if( $notIn )    {
                            $subquery = $subquery->whereNotIn('id', $notIn);
                        }
                        return $subquery;
                    }]);
            }   else    {
                $subquery = $query->with('children');
                if( $notIn )    {
                    $subquery = $subquery->whereNotIn('id', $notIn);
                }
                return $subquery;
            }
        }

        $query = $query->withOrganization($organization, true)->orderByRaw($orderByRaw);

        return $query;

    }

    public function index( $organization, $params = [] ) {

        $params = array_merge($this->_buildParams(), $params);
        $query = $this->_buildQuery($organization, $params)
            ->whereNull('parent_id')
            ->withOrganization($organization);

        if( $params['minimal'] ) {
            $results = $query->get();
            // return $results;
            if( $params['flatlist'] ) {
                // exit;
                $newResults = collect([]);
                _flatten($results, $newResults);
                return $newResults;
            }
            return $results;
        }

        // if( $params['paginate'] ) {
        //     return $query->paginate( $params['limit']);
        // }

        $results = $query->paginate( $params['limit']);
        return $results;
    }

    public function getSubprograms( $organization, $program, $params = [] ) {
        $params = array_merge($this->_buildParams(), $params);
        $query = $this->_buildQuery($organization, $params)
            ->where('parent_id', $program->id)
            ->withOrganization($organization, true)
            ;

        if( $params['minimal'] ) {
            $results = $query->get();
            // return $results;
            if( $params['flatlist'] ) {
                // exit;
                $newResults = collect([]);
                _flatten($results, $newResults);
                return $newResults;
            }

            return $results;
        }

        // if( $params['paginate'] ) {
        //     return $query->paginate( $params['limit']);
        // }

        $results = $query->paginate( $params['limit']);
        return $results;
    }

    public function getParticipants($program, $paginate = false)   {
        return $this->userService->getParticipants($program, $paginate);
    }

    public function getAvailableToAddAsSubprogram($organization, $program) {
        // return $program->ancestorsAndSelf()->get()->pluck('id');
        $exclude = $program->ancestorsAndSelf()->get()->pluck('id');
        // pr($exclude);
        $programs = $this->index($organization, [
            'except' => $exclude->toArray(),
            'minimal'=>true,
            'flatlist'=>true
        ]);
        // return $programs;
        $subprograms = $this->getSubprograms( $organization, $program);
        $available = $this->getDifference( $programs, $subprograms );
        return $available;
    }

    public function getAvailableToMoveSubprogram($organization, $program) {

        $parent = $program->parent()->select('id')->first();
        // pr($program->toArray());
        $children = $program->children;
        // pr($children->toArray());
        // exit;
        $topLevelProgram = $program->getRoot(['id', 'name']);
        $exclude[] = $program->id; // exclude self
        $exclude[] = $parent->id; // exlude parent

        if( $children ) {
            collectIdsInATree($children->toArray(), $exclude);
        }

        // pr($exclude);
        // exit;
        // $subprograms = $this->getSubprograms( $organization, Program::find($topLevelProgram, [
        //     'except' => $exclude,
        //     'minimal'=>true,
        //     // 'flatlist'=>true
        // ]);
        // if( $topLevelProgram->id != $parent->id) {
        //     $subprograms->prepend($topLevelProgram); //push at the top
        // }
        $program2 = Program::find($topLevelProgram->id)
        ->with(['children' => function($query)  {
            $subquery = $query->select('id','name','parent_id');
            // if( $exclude )    {
            //     $subquery = $subquery->whereNotIn('id', $exclude);
            // }
            return $subquery;
        }])
        ->withOrganization($organization, 1);

        return
            [
                'tree' => $program2->first(),
                'exclude' => $exclude
            ];
        // return $subprograms;
    }

    public function getDifference($programs, $subprograms) {
        $ids = array_column($subprograms->toArray(), 'id');
        $diff = collect([]);
        foreach( $programs->toArray() as $program) {
            if( !in_array($program['id'], $ids)) {
                $diff->push($program);
            }

        }
        return $diff;
    }

    public function unlinkNodeWithSubtree($organization, $program) {
        if( !$program->children->isEmpty() )  {
            foreach($program->children as $children)  {
                $children->parent_id = null;
                $children->save();
                $this->unlinkNodeWithSubtree($organization, $children);
            }
        }
        $program->parent_id = null;
        $program->save();
    }

    public function unlinkNode($organization, $program) {
        $parent_id = $program->parent ? $program->parent->id : null;
        if(!$program->children->isEmpty())  {
            foreach($program->children as $children)    {
                $children->parent_id = $parent_id;
                $children->save();
            }
        }
        $program->parent_id = null;
        $program->save();
    }

    public function listAvailableProgramsToAdd( $organization, $domain)    {
        $keyword = request()->get('keyword');
        if( !$domain->programs->isEmpty() )   {
            // return $domain->programs;
            $existing = $domain->programs->pluck('id');
            //The logic here depends upon an assumption that a domain can have programs/subprograms from within only one program tree.
            $firstProgram = $domain->programs()->first();
            if( is_null($firstProgram->parent_id) ) {
                $rootAncestor = $firstProgram;
            }   else {
                $rootAncestor = $firstProgram->rootAncestor()->first();
            }
            $constraint = function ($query) use ($rootAncestor) {
                $query->where('id', $rootAncestor->id);
            };
            $query = Program::treeOf($constraint)->whereNotIn('id', $existing);
        }   else {
            $constraint = function ($query) {
                $query->whereNull('parent_id');
            };
            $query = Program::treeOf($constraint)->where('organization_id', $organization->id);
        }

        if( $keyword )
        {
            $query = $query->where(function($query1) use($keyword) {
                $query1->orWhere('id', 'LIKE', "%{$keyword}%")
                ->orWhere('name', 'LIKE', "%{$keyword}%");
            });
        }

        $programs = $query->select('id', 'name')->get();
        return $programs;
    }

    public function getDescendents( $program, $includeSelf = false ) {
        if( $includeSelf )  {
            return $program->descendantsAndSelf()->get()->toTree();
        }
        return $program->descendants()->get()->toTree();
    }

    public function update($program, $data)    {
        if( isset($data['address']) )   {
            if( $program->address()->exists() )   {
                $program->address()->update($data['address']);   
            }   else  {
                $program->address()->create($data['address']);   
            }
            unset($data['address']);
        }
        if($program->update($data)) {
            if($program->setup_fee > 0 && !$this->isFeeAccountExists($program))  {
                $program->create_setup_fee_account();
            }
            return $program;
        }
    }
    /**
     * @param Program $program
     * @param array $where
     * @return mixed
     */
    public function getDescendentsWithCondition( Program $program, array $where ) {
        $result = $program->descendants()
            ->where($where)
            ->get()
            ->toTree();
        return $result;
    }

    public function isFeeAccountExists( $program )    {
        DB::statement("SET SQL_MODE=''"); // to prevent groupby error. see shorturl.at/qrQ07

        $qry_statement = "
        SELECT 
            posts.*,
            posts.created_at as posting_timestamp,
            jet.type as journal_event_type
        	FROM postings posts
            INNER JOIN journal_events je ON je.id = posts.journal_event_id
            INNER JOIN journal_event_types jet ON jet.id = je.journal_event_type_id
            INNER JOIN accounts a ON a.id = posts.account_id
            INNER JOIN account_types atypes ON atypes.id = a.account_type_id
            INNER JOIN finance_types ftypes ON ftypes.id = a.finance_type_id
            INNER JOIN medium_types mtypes ON mtypes.id = a.medium_type_id
            INNER JOIN currencies c ON c.id = a.currency_type_id
        WHERE
            a.account_holder_id = :program_account_holder_id
            and jet.type = :journal_event_type
        GROUP BY
        posts.id
        ORDER BY
            journal_event_type, posting_timestamp ASC;
        ";
		
        try {
			$result = DB::select( DB::raw($qry_statement), array(
				'journal_event_type' => 'Charge setup fee to program',
				'program_account_holder_id' => $program->account_holder_id
			));
		} catch (Exception $e) {
			throw new \RuntimeException ( 'Could not get fee account information in  ProgramService:isFeeAccountExists. DB query failed.', 500 );
		}

        if( sizeof($result) > 0) return true;
        return false;
    }

    public function transferMonies(Program $program, $data)    {
        $topLevelProgram = $program->rootAncestor()->select(['id', 'name'])->first();
        if( !$topLevelProgram ) {
            $topLevelProgram = $program;
        }
        $programs = $topLevelProgram->descendants()->depthFirst()->whereNotIn('id', [$program->id])->select(['id', 'name'])->get();
        $balance = Account::read_available_balance_for_program ( $program );
        return 
            [
                'program' => $program,
                'programs' => $programs,
                'balance' => $balance,
            ]
        ;
    }
}
