<?php

namespace App\Services;

use App\Services\Program\Traits\ChargeFeeTrait;
use App\Services\Program\TransferMoniesService;
use App\Services\ProgramTemplateService;
use App\Models\Traits\IdExtractor;
use App\Services\AccountService;
use App\Services\UserService;
use App\Models\Status;
use App\Models\Event;
use App\Models\Program;
use DB;

class ProgramService
{
    use IdExtractor;
    use ChargeFeeTrait;

    public $program;
    public $program_account_holder_id;
    public $user_account_holder_id;

    private UserService $userService;
    private AccountService $accountService;
    private ProgramTemplateService $programTemplateService;
    private TransferMoniesService $transferMoniesService;
    private ProgramsTransactionFeeService $programsTransactionFeeService;

    public function __construct(
        UserService $userService,
        AccountService $accountService,
        TransferMoniesService $transferMoniesService,
        ProgramTemplateService $programTemplateService,
        ProgramsTransactionFeeService $programsTransactionFeeService
    ) {
        $this->userService = $userService;
        $this->accountService = $accountService;
        $this->transferMoniesService = $transferMoniesService;
        $this->programTemplateService = $programTemplateService;
        $this->programsTransactionFeeService = $programsTransactionFeeService;
    }

    const DEFAULT_PARAMS = [
        'orgId' => '', //array of organization ids in comma separated
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

    private function _buildParams($override = [])
    {
        // pr($override);
        $params = [];
        $orgId = ! empty($override['orgId']) ? $override['orgId'] : request()->get('orgId', '');
        $status = ! empty($override['status']) ? $override['status'] : request()->get('status', '');
        $keyword = ! empty($override['keyword']) ? $override['keyword'] : request()->get('keyword', '');
        $sortby = ! empty($override['sortby']) ? $override['sortby'] : request()->get('sortby', 'id');
        $direction = ! empty($override['direction']) ? $override['direction'] : request()->get('direction', 'asc');
        $tree = isset($override['tree']) ? $override['tree'] : request()->get('tree', true);
        $minimal = ! empty($override['minimal']) ? $override['minimal'] : request()->get('minimal', false);
        $flatlist = ! empty($override['flatlist']) ? $override['flatlist'] : request()->get('flatlist', false);
        $except = ! empty($override['except']) ? $override['except'] : request()->get('except', '');
        $limit = ! empty($override['limit']) ? $override['limit'] : request()->get('limit', 10);
        $paginate = ! empty($override['paginate']) ? $override['paginate'] : request()->get('paginate', true);
        $params['orgId'] = $orgId;
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

    private function _buildQuery($organization, $params = [])
    {

        $params = array_merge(self::DEFAULT_PARAMS, self::_buildParams($params));

        extract($params);

        $where = [];

        if ($sortby == "name") {
            $collation = "COLLATE utf8mb4_unicode_ci"; //COLLATION is required to support case insensitive ordering
            $orderByRaw = "{$sortby} {$collation} {$direction}";
        } else {
            $orderByRaw = "{$sortby} {$direction}";
        }

        $query = Program::where($where);

        if( $orgId )
        {
            $orgIds = explode(',', $orgId);
            $query->whereIn('organization_id', $orgIds);
        }

        if ($status) {
            $statuses = explode(',', $status);
            $statusIds = [];
            foreach ($statuses as $s){
                $statusIds[] = Program::getStatusIdByName($s);
            }
            $query->whereIn('status_id', $statusIds);
            $statusIdDeleted = Program::getIdStatusDeleted();

            if( in_array($statusIdDeleted, $statusIds))
            {
                $query = $query->withTrashed();
            }
        }

        if ($keyword) {
            $query = $query->where(function ($query1) use ($keyword) {
                $query1->orWhere('id', 'LIKE', "%{$keyword}%")
                    ->orWhere('name', 'LIKE', "%{$keyword}%");
            });
        }

        $notIn = '';

        if ($except) {
            if (is_array($except)) {
                $notIn = $except;
            } elseif (strpos($except, ',')) {
                $notIn = explode(trim($except));
            } elseif ((int)$except) {
                $notIn = [$except];
            }
            if ($notIn) {
                $query = $query->whereNotIn('id', $notIn);
            }
        }

        if ($minimal) {
            $query = $query->select('id', 'name');
        }

        if ($tree) {
            if ($minimal) {
                $query = $query->with([
                    'children' => function ($query) use ($notIn) {
                        $subquery = $query->select('id', 'name', 'parent_id');
                        if ($notIn) {
                            $subquery = $subquery->whereNotIn('id', $notIn);
                        }
                        $subquery->with(['status']);
                        return $subquery;
                    },
                    'status'
                ]);
            } else {
                $subquery = $query->with([
                    'children' => function ($query) {
                        $query->with(['status']);
                        return $query;
                    },
                    'status'
                ]);
                if ($notIn) {
                    $subquery = $subquery->whereNotIn('id', $notIn);
                }
                return $subquery;
            }
        }

        $query = $query->withOrganization($organization, true)->orderByRaw($orderByRaw);

        return $query;

    }

    public function index($organization, $params = [])
    {
        $all = $params['all'] ?? false;
        $params = array_merge($this->_buildParams(), $params);
        $query = $this->_buildQuery($organization, $params);
        if (!$all) {
            $query->whereNull('parent_id');
        }
        $query->withOrganization($organization);

        if ($params['minimal']) {
            $results = $query->get();
            if ($params['flatlist']) {
                $newResults = _flatten($results);
                return $newResults;
            }
            return $results;
        }
        $results = $query->paginate( $params['limit']);
        return $results;
    }

    public function getSubprograms($organization, $program, $params = [])
    {
        $params = array_merge($this->_buildParams(), $params);
        $query = $this->_buildQuery($organization, $params)
            ->where('parent_id', $program->id)
            ->withOrganization($organization, true);

        if ($params['minimal']) {
            $results = $query->get();
            if ($params['flatlist']) {
                $newResults = _flatten($results);
                return $newResults;
            }

            return $results;
        }

        // if( $params['paginate'] ) {
        //     return $query->paginate( $params['limit']);
        // }

        $results = $query->paginate($params['limit']);
        return $results;
    }

    public function getParticipants($program, $paginate = false)
    {
        return $this->userService->getParticipants($program, $paginate);
    }

    public function getAvailableToAddAsSubprogram($organization, $program)
    {
        // return $program->ancestorsAndSelf()->get()->pluck('id');
        $exclude = $program->ancestorsAndSelf()->get()->pluck('id');
        // pr($exclude);
        $programs = $this->index($organization, [
            'except' => $exclude->toArray(),
            'minimal' => true,
            'flatlist' => true
        ]);
        // return $programs;
        $subprograms = $this->getSubprograms($organization, $program);
        $available = $this->getDifference($programs, $subprograms);
        return $available;
    }

    public function getAvailableToMoveSubprogram($organization, $program)
    {

        $parent = $program->parent()->select('id')->first();
        // pr($program->toArray());
        $children = $program->children;
        // pr($children->toArray());
        // exit;
        $topLevelProgram = $program->getRoot(['id', 'name']);
        $exclude[] = $program->id; // exclude self
        $exclude[] = $parent->id; // exlude parent

        if ($children) {
            collectIdsInATree($children->toArray(), $exclude);
        }

        $program2 = Program::find($topLevelProgram->id)
            ->with([
                'children' => function ($query) {
                    $subquery = $query->select('id', 'name', 'parent_id');
                    // if( $exclude )    {
                    //     $subquery = $subquery->whereNotIn('id', $exclude);
                    // }
                    return $subquery;
                }
            ])
            ->withOrganization($organization, 1);

        return
            [
                'tree' => $program2->first(),
                'exclude' => $exclude
            ];
        // return $subprograms;
    }

    public function getDifference($programs, $subprograms)
    {
        $ids = array_column($subprograms->toArray(), 'id');
        $diff = collect([]);
        foreach ($programs->toArray() as $program) {
            if ( ! in_array($program['id'], $ids)) {
                $diff->push($program);
            }

        }
        return $diff;
    }

    public function unlinkNodeWithSubtree($organization, $program)
    {
        if ( ! $program->children->isEmpty()) {
            foreach ($program->children as $children) {
                $children->parent_id = null;
                $children->save();
                $this->unlinkNodeWithSubtree($organization, $children);
            }
        }
        $program->parent_id = null;
        $program->save();
    }

    public function unlinkNode($organization, $program)
    {
        $parent_id = $program->parent ? $program->parent->id : null;
        if ( ! $program->children->isEmpty()) {
            foreach ($program->children as $children) {
                $children->parent_id = $parent_id;
                $children->save();
            }
        }
        $program->parent_id = null;
        $program->save();
    }

    public function listAvailableProgramsToAdd($organization, $domain)
    {
        $keyword = request()->get('keyword');
        if ( ! $domain->programs->isEmpty()) {
            // return $domain->programs;
            $existing = $domain->programs->pluck('id');
            //The logic here depends upon an assumption that a domain can have programs/subprograms from within only one program tree.
            $firstProgram = $domain->programs()->first();
            if (is_null($firstProgram->parent_id)) {
                $rootAncestor = $firstProgram;
            } else {
                $rootAncestor = $firstProgram->rootAncestor()->first();
            }
            $constraint = function ($query) use ($rootAncestor) {
                $query->where('id', $rootAncestor->id);
            };
            $query = Program::treeOf($constraint)->whereNotIn('id', $existing);
        } else {
            $constraint = function ($query) {
                $query->whereNull('parent_id');
            };
            $query = Program::treeOf($constraint)->where('organization_id', $organization->id);
        }

        if ($keyword) {
            $query = $query->where(function ($query1) use ($keyword) {
                $query1->orWhere('id', 'LIKE', "%{$keyword}%")
                    ->orWhere('name', 'LIKE', "%{$keyword}%");
            });
        }

        $programs = $query->select('id', 'name')->get();
        return $programs;
    }

    public function getDescendents($program, $includeSelf = false)
    {
        if ($includeSelf) {
            return $program->descendantsAndSelf()->get()->toTree();
        }
        return $program->descendants()->get()->toTree();
    }

    public function create($data)
    {
        if (isset($data['status'])) { //If status present in "string" format
            $data['status_id'] = Program::getStatusIdByName($data['status']);
            unset($data['status']);
        }
        else if(empty($data['status_id'])) //if, the status_id also not set
        {
            //Set default status
            $data['status_id'] = Program::getIdStatusActive();
        }
        return Program::createAccount($data);
    }

    public function update($program, $data)
    {
        if (isset($data['address'])) {
            if ($program->address()->exists()) {
                $program->address()->update($data['address']);
            } else {
                $program->address()->create($data['address']);
            }
            unset($data['address']);
        }
        if (!empty($data['status'])) { //If status present in string format
            $data['status_id'] = !empty($data['status_id']) ? $data['status_id'] : Program::getStatusIdByName($data['status']);
            unset($data['status']);
        }
        if( empty($data['status_id']) )
        {   //set default status to "Active"
            $data['status_id'] = Program::getIdStatusActive();
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
    public function getDescendentsWithCondition(Program $program, array $where)
    {
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

    public function getTransferMonies(Program $program)    {
        $topLevelProgram = $program->rootAncestor()->select(['id', 'name'])->first();
        if( !$topLevelProgram ) {
            $topLevelProgram = $program;
        }
        $programs = $topLevelProgram->descendantsAndSelf()->depthFirst()->whereNotIn('id', [$program->id])->select(['id', 'name'])->get();
        $balance = $this->accountService->readAvailableBalanceForProgram ( $program );
        return
            [
                'program' => $program,
                'programs' => $programs,
                'balance' => $balance,
            ]
        ;
    }

    public function submitTransferMonies(Program $program, $data)    {
        if(sizeof($data["amounts"]) > 0)    {
            $result = [];
            foreach($data["amounts"] as $programId => $amount)  {
                $balance = $this->accountService->readAvailableBalanceForProgram ( $program );
                if ($amount > $balance) {
                    throw new \RuntimeException ( "Account balance has insufficient funds to transfer $" . $amount, 400 );
                }
                $user_account_holder_id = auth()->user()->account_holder_id;
                $program_account_holder_id = $program->account_holder_id;
                $new_program_account_holder_id = $program->where('id', $programId)->first()->account_holder_id;
                $result[$programId] = $this->transferMoniesService->transferMonies($user_account_holder_id, $program_account_holder_id, $new_program_account_holder_id, $amount);
            }
            if( sizeof($data["amounts"]) == sizeof($result))    {
                $balance = $this->accountService->readAvailableBalanceForProgram ( $program );
                return ['success'=>true, 'transferred' => $result, 'balance' => $balance];
            }
        }
    }

    /**
     * If the program must pay in advance for their awards, verify that they have they have enough funds to cover all of the awards
     *
     * @param Program $program
     * @param Event $event
     * @param array $userIds
     * @param $amount
     * @param array $extraArgs
     * @return bool
     */
    public function canProgramPayForAwards(
        Program $program,
        Event $event,
        array $userIds,
        float $amount,
        array $extraArgs = []
    ): bool {
        if ( $program->programIsInvoiceForAwards() ) {
            // If we invoice for awards, the program will always pay later
            return true;
        } else {
            /** @var EventType $eventType */
            $eventType = $event->eventType()->firstOrFail();
            $isEventTypeBadge = $eventType->isEventTypeBadge();
            $isEventTypePeer2PeerBadge = $eventType->isEventTypePeer2PeerBadge();

            // If the event is badge - The amount should be zero, So we don't need to check the funds.
            if ($isEventTypeBadge || $isEventTypePeer2PeerBadge) {
                return true;
            }

            $transaction_fee = $this->programsTransactionFeeService->calculateTransactionFee($program, $amount);
            // Get the total of transaction fees and award based on how many people will be awarded
            $total_transaction_fee = $transaction_fee * count($userIds);

            $total_awards = $amount * count($userIds);
            $program_balance = $this->readAvailableBalance($program);

            if (isset($extraArgs['pending_amount']) && $extraArgs['pending_amount'] > 0) {
                $program_balance = $program_balance - floatval($extraArgs['pending_amount']);
            }
            return ($program_balance >= $total_awards + $total_transaction_fee);
        }
    }

    /**
     * Returns the available balance of the given program
     *
     * @param Program $program
     * @return float
     */
    public function readAvailableBalance(Program $program): float
    {
        return $this->accountService->readAvailableBalanceForProgram($program);
    }
    /**
     * Returns the list of billable descedents under a given program
     *
     * @param Program $program
     * @return float
     */
    public function getBillableDescendants(Program $program): Array
    {
        $descendants = $program->descendants()->get();
        $billable_programs = [];
        $programs_to_skip = [];
        foreach( $descendants as $subProgram)   {
            $rank = explode ( ".", $subProgram->path );
            $b_Skip_This = false;
            if (count ( $programs_to_skip ) > 0) {
				foreach ( $programs_to_skip as $program_to_skip ) {
					if (in_array ( $program_to_skip, $rank )) {
						$b_Skip_This = true;
					}
				}
			}
            if ( !$subProgram->bill_parent_program && !$b_Skip_This) {
				$billable_programs[$subProgram->id] = $subProgram;

			} else {
				$programs_to_skip[] = $program->id;
			}
        }
        return $billable_programs;
    }
    public function listStatus()
    {
        return Status::where('context', 'Programs')->get();
    }

    public function updateStatus($validated, $program)
    {
        return $program->update( ['status_id' => $validated['program_status_id']] );
    }

    public function getTemplate(Program $program)
    {
        return $this->programTemplateService->getTemplate($program);
    }
}
