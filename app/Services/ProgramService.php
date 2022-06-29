<?php
namespace App\Services;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Traits\IdExtractor;
use App\Models\Program;
use App\Models\Role;
use App\Models\User;
use DB;

class ProgramService 
{
    use IdExtractor;

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

        $query = $query->orderByRaw($orderByRaw);

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
        // DB::enableQueryLog();
        $params = array_merge($this->_buildParams(), $params);
        $query = $this->_buildQuery($organization, $params)
            ->where('parent_id', $program->id)
            ->withOrganization($organization);

        if( $params['minimal'] ) {
            $results = $query->get();
            // return $results;
            if( $params['flatlist'] ) {
                // exit;
                // pr(DB::getQueryLog());
                $newResults = collect([]);
                _flatten($results, $newResults);
                return $newResults;
            }
            
            return $results;
        }

        // if( $params['paginate'] ) {
        //     return $query->paginate( $params['limit']);
        // }

        // pr(DB::getQueryLog());

        $results = $query->paginate( $params['limit']);
        return $results;    
    }

    public function getParticipants($program, $paginate = false)   {
        $program_id = self::extractId($program);
        if( !$program_id ) return;
        $query = User::whereHas('roles', function (Builder $query) use($program_id) {
            $query->where('name', 'LIKE', config('roles.participant'))
            ->where('model_has_roles.program_id', $program_id);
        });
        if( $paginate ) {
            return $query->paginate();
        }   else    {
            return $query->get();
        }
    }

    public function getAvailableToAddAsSubprogram($organization, $program) {
        $programs = $this->index($organization, [
            'except' => $program->id, 
            'minimal'=>true, 
            'flatlist'=>true
        ]);
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
        ->withOrganization($organization);
        
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
}
