<?php

namespace App\Services;

use App\Models\ProgramExtra;
use App\Models\ProgramTransactionFee;
use DB;
use Illuminate\Database\Eloquent\Collection;

use App\Services\Program\Traits\ChargeFeeTrait;
use App\Services\ProgramTemplateService;
use App\Models\Traits\IdExtractor;
use App\Models\JournalEventType;
use App\Services\AccountService;
use App\Services\UserService;
use App\Models\AccountHolder;
use App\Models\EventXmlData;
use App\Models\JournalEvent;
use App\Models\FinanceType;
use App\Models\MediumType;
use App\Models\Giftcode;
use App\Models\Account;
use App\Models\Program;
use App\Models\Posting;
use App\Models\Status;
use App\Models\Event;

class ProgramService
{
    use IdExtractor;
    use ChargeFeeTrait;

    public $program;
    public $program_account_holder_id;
    public $user_account_holder_id;

    private UserService $userService;
    private AccountService $accountService;
    private InvoiceService $InvoiceService;
    private ProgramTemplateService $programTemplateService;

    public function __construct(
        UserService $userService,
        AccountService $accountService,
        ProgramTemplateService $programTemplateService,
    ) {
        $this->userService = $userService;
        $this->accountService = $accountService;
        $this->programTemplateService = $programTemplateService;
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
        'except' => [], //array of primary keys,
        'minimalFields' => Program::MIN_FIELDS,
        'programId' => false,
    ];

    private function _buildParams($override = [])
    {
        // pr($override);
        $params = [];
        $programsId = !empty($override['programsId']) ? $override['programsId'] : request()->get('programsId', '');
        $orgId = !empty($override['orgId']) ? $override['orgId'] : request()->get('orgId', '');
        $programId = !empty($override['programId']) ? $override['programId'] : request()->get('programId', '');
        $status = !empty($override['status']) ? $override['status'] : request()->get('status', '');
        $keyword = !empty($override['keyword']) ? $override['keyword'] : request()->get('keyword', '');
        $sortby = !empty($override['sortby']) ? $override['sortby'] : request()->get('sortby', 'id');
        $direction = !empty($override['direction']) ? $override['direction'] : request()->get('direction', 'asc');
        $tree = filter_var(isset($override['tree']) ? $override['tree'] : request()->get('tree', true), FILTER_VALIDATE_BOOLEAN);
        $minimal = filter_var(isset($override['minimal']) ? $override['minimal'] : request()->get('minimal', false), FILTER_VALIDATE_BOOLEAN);
        $flatlist = filter_var(isset($override['flatlist']) ? $override['flatlist'] : request()->get('flatlist', false), FILTER_VALIDATE_BOOLEAN);
        $except = isset($override['except']) ? $override['except'] : request()->get('except', '');
        $limit = isset($override['limit']) ? $override['limit'] : request()->get('limit', 10);

        $paginate = filter_var(isset($override['paginate']) ? $override['paginate'] : request()->get('paginate', true), FILTER_VALIDATE_BOOLEAN);

        $all = filter_var(isset($override['all']) ? $override['all'] : request()->get('all', false), FILTER_VALIDATE_BOOLEAN);

        $params['programsId'] = $programsId;
        $params['orgId'] = $orgId;
        $params['programId'] = $programId;
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
        $params['all'] = $all;
        // dd($params);
        // pr($params);
        return $params;
    }

    private function _buildQuery($organization = null, $params = [])
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

        if ($programsId) {
            $programsId = explode(',', $programsId);
            $query->whereIn('id', $programsId);
        }
        if ($orgId) {
            $orgIds = explode(',', $orgId);
            $query->whereIn('organization_id', $orgIds);
        }

        if ($status) {
            $statuses = explode(',', $status);
            $statusIds = [];
            foreach ($statuses as $s) {
                $statusIds[] = Program::getStatusIdByName($s);
            }
            $query->whereIn('status_id', $statusIds);
            $statusIdDeleted = Program::getIdStatusDeleted();

            if (in_array($statusIdDeleted, $statusIds)) {
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
                $notIn = explode(trim($except), ',');
            } elseif ((int)$except) {
                $notIn = [$except];
            }
            if ($notIn) {
                $query = $query->whereNotIn('id', $notIn);
            }
        }

        if ($minimal) {
            $query = $query->select($minimalFields);
        }

        if ($tree) {
            if ($minimal) {
                $query = $query->with([
                    'childrenMinimal' => function ($query) use ($notIn, $minimalFields) {
                        $subquery = $query->select($minimalFields);
                        if ($notIn) {
                            $subquery = $subquery->whereNotIn('id', $notIn);
                        }
                        return $subquery;
                    },
                    'status'
                ]);
            } else {
                $query = $query->with([
                    'children' => function ($subquery) {
                        $subquery->with(['status']);
                        return $subquery;
                    },
                    'status'
                ]);
                if ($notIn) {
                    $query = $query->whereNotIn('id', $notIn);
                }
            }
        }
        if ($organization) {
            $query = $query->withOrganization($organization)->orderByRaw($orderByRaw);
        } else {
            $query = $query->orderByRaw($orderByRaw);
        }
        return $query;
    }

    public function index($organization, $params = [])
    {
        $params = $this->_buildParams($params);
        $query = $this->_buildQuery($organization, $params);

        if ((int) $params['programId']) {
            $query->where('parent_id', $params['programId']);
            $query->orWhere('id', $params['programId']);
        }

        if (!$params['all'] && !$params['programId']) {
            $query->whereNull('parent_id');
        }

        if ($params['paginate']) {
            $results = $query->paginate($params['limit']);
            if ($params['minimal']) {
                $results->getCollection()->transform(function ($value) {
                    $value = childrenizeModel($value);
                    return $value;
                });
            }
        } else {
            $results = $query->get();
            if ($params['minimal']) {
                $results = childrenizeCollection($results);
            }
            if ($params['flatlist']) {
                $results = _flatten($results);
            }
        }
        return $results;
    }

    public function all($organization, $params = [])
    {
        $params = $this->_buildParams($params);
        $query = $this->_buildQuery($organization, $params);
        if (!$params['all']) {
            $query->whereNull('parent_id');
        }

        if ($params['paginate']) {
            $results = $query->paginate($params['limit']);
            if ($params['minimal']) {
                $results->getCollection()->transform(function ($value) {
                    $value = childrenizeModel($value);
                    return $value;
                });
            }
        } else {
            $results = $query->get();
            if ($params['minimal']) {
                $results = childrenizeCollection($results);
            }
            if ($params['flatlist']) {
                $results = _flatten($results);
            }
        }
        return $results;
    }

    public function create($data)
    {
        if (isset($data['status'])) { //If status present in "string" format
            $data['status_id'] = Program::getStatusIdByName($data['status']);
            unset($data['status']);
        } else if (empty($data['status_id'])) //if, the status_id also not set
        {
            //Set default status
            $data['status_id'] = Program::getIdStatusActive();
        }

        $program_account_holder_id = AccountHolder::insertGetId(['context' => 'Program', 'created_at' => now()]);

        if (isset($data['invoice_for_awards']) && $data['invoice_for_awards']) {
            $data['allow_creditcard_deposits'] = 1;
        }
        if (!isset($data['expiration_rule_id'])) {
            $data['expiration_rule_id'] = 3; //End of Next Year
        }
        if (!empty($data['status'])) { //If status present in string format
            $data['status_id'] = !empty($data['status_id']) ? $data['status_id'] : Program::getStatusIdByName($data['status']);
            unset($data['status']);
        }
        if (empty($data['status_id'])) {   //set default status to "Active"
            $data['status_id'] = Program::getIdStatusActive();
        }
        $program = Program::create($data + ['account_holder_id' => $program_account_holder_id]);
        $liability = FinanceType::getIdByName('Liability');
        $asset = FinanceType::getIdByName('Asset', true);
        $monies_mt = MediumType::getIdByName('Monies', true);
        $default_accounts = array(
            array(
                'account_type' => 'Monies Deposits',
                'finance_type' => $liability,
                'medium_type' => $monies_mt
            ),
            array(
                'account_type' => 'Monies Due to Owner',
                'finance_type' => $asset,
                'medium_type' => $monies_mt
            ),
            array(
                'account_type' => 'Monies Fees',
                'finance_type' => $liability,
                'medium_type' => $monies_mt
            ),
            array(
                'account_type' => 'Monies Paid to Progam',
                'finance_type' => $liability,
                'medium_type' => $monies_mt
            ),
            array(
                'account_type' => 'Monies Redeemed',
                'finance_type' => $liability,
                'medium_type' => $monies_mt
            ),
            array(
                'account_type' => 'Monies Shared',
                'finance_type' => $liability,
                'medium_type' => $monies_mt
            ),
            array(
                'account_type' => 'Monies Transaction',
                'finance_type' => $liability,
                'medium_type' => $monies_mt
            )
        );

        Account::create_multi_accounts($program_account_holder_id, $default_accounts);

        //TODO ??
        // $this->tie_sub_program ( $program_account_holder_id, $program_account_holder_id );

        // $default_participant_role_id = Role::getIdByNameAndOrg("Participant", $program->organization_id);

        // $this->award_levels_model->create ( $program_account_holder_id, 'default' );

        $program->create_setup_fee_account();
        cache()->forget(Program::CACHE_FULL_HIERARCHY_NAME);
        return $program;
    }

    public function update($program, $data)
    {
        unset($data['administrative_fee_calculation']);
        unset($data['administrative_fee']);
        unset($data['administrative_fee_factor']);

        if (!empty($data['program_extras'])) {
            $programExtra = new ProgramExtra();
            $programExtra->updateProgramExtra($data['program_extras']['id'], $data['program_extras']);
            unset($data['program_extras']);
        } else {
            unset($data['program_extras']);
        }

        $programExtra = new ProgramTransactionFee();
        $programTransactionFee = [];
        foreach ($data['program_transaction_fee'] as $val) {
            if (!empty($val['tier_amount']) && !empty($val['transaction_fee'])) {
                $std = new \stdClass();
                $std->tier_amount = (float)$val['tier_amount'];
                $std->transaction_fee = (float)$val['transaction_fee'];
                $programTransactionFee[] = $std;
            }
        }
        $programExtra->updateTransactionFees($program->id, $programTransactionFee);
        unset($data['program_transaction_fee']);

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
        if (empty($data['status_id'])) {   //set default status to "Active"
            $data['status_id'] = Program::getIdStatusActive();
        }
        if ($program->update($data)) {
            if ($program->setup_fee > 0 && !$this->isFeeAccountExists($program)) {
                $program->create_setup_fee_account();
            }
            if (isset($data['use_budget_cascading']) || isset($data['use_cascading_approvals'])) {
                // Get all descendant programs recursively
                $childPrograms = $this->getHierarchyByProgramIdd(null, $program->id);
                // Update each program recursively
                $this->recursiveUpdate($childPrograms, $data);
            }
            cache()->forget(Program::CACHE_FULL_HIERARCHY_NAME);
            return $program;
        }
    }

    protected function recursiveUpdate($programs, $data)
    {
        foreach ($programs as $program) {
            // Update the current program
            $program->update([
                'use_budget_cascading' => $data['use_budget_cascading'],
                'use_cascading_approvals' => $data['use_cascading_approvals'],
            ]);
            // Load children relationship and recursively update them if any
            $program->load('children');
            if ($program->children) {
                $this->recursiveUpdate($program->children, $data);
            }
        }
    }

    public function getHierarchyByProgramIdd($organization, $programId)
    {
        try {
            $program = Program::find($programId);
            $programsId = $program->descendantsAndSelf()->get()->pluck('id')->toArray();

            $minimalFields = Program::MIN_FIELDS;
            $query = Program::query();
            $query->whereIn('id', $programsId);
            $query = $query->select($minimalFields);
            $query = $query->with(['childrenMinimal']);
            $result = $query->get();
            return $result;
        } catch (\Exception $e) {
            throw new \Exception(sprintf("Error %s in line: %d or file: %s", $e->getMessage(), $e->getLine(), $e->getFile()));
        }
    }

    public function getHierarchy($organization)
    {
        try {
            $minimalFields = Program::MIN_FIELDS;
            $query = Program::query();
            $query->whereNull('parent_id');
            $query = $query->select($minimalFields);
            $query = $query->with([
                'childrenMinimal' => function ($query) use ($minimalFields) {
                    $subquery = $query->select($minimalFields);
                    return $subquery;
                }
            ]);
            $result = $query->get();
            return childrenizeCollection($result);
        } catch (\Exception $e) {
            throw new \Exception(sprintf("Error %s in line: %d or file: %s", $e->getMessage(), $e->getLine(), $e->getFile()));
        }
    }

    public function getHierarchyByProgramId($organization, $programId)
    {
        try {
            $program = Program::find($programId);
            $programsId = $program->descendantsAndSelf()->get()->pluck('id')->toArray();

            $minimalFields = Program::MIN_FIELDS;
            $query = Program::query();
            $query->where('parent_id', $program->parent_id);
            $query->whereIn('id', $programsId);
            $query = $query->select($minimalFields);
            $query = $query->with([
                'childrenMinimal' => function ($query) use ($minimalFields) {
                    $subquery = $query->select($minimalFields);
                    return $subquery;
                }
            ]);
            $result = $query->get();
            return childrenizeCollection($result);
        } catch (\Exception $e) {
            throw new \Exception(sprintf("Error %s in line: %d or file: %s", $e->getMessage(), $e->getLine(), $e->getFile()));
        }
    }

    public function getSubprograms($organization, $program, $params = [])
    {
        $params = array_merge($this->_buildParams(), $params);
        $query = $this->_buildQuery($organization, $params)
            ->where('parent_id', $program->id)
            ->withOrganization($organization, true);
        // hierarchy=1&
        // if ($params['minimal']) {
        //     $results = $query->get();
        //     if ($params['flatlist']) {
        //         $newResults = _flatten($results);
        //         return $newResults;
        //     }

        //     return $results;
        // }

        if ($params['paginate']) {
            return $query->paginate($params['limit']);
        }
        $results = $query->get();
        if ($params['flatlist']) {
            $newResults = _flatten($results);
            return $newResults;
        }
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
            'flatlist' => true,
            'paginate' => 0
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
            if (!in_array($program['id'], $ids)) {
                $diff->push($program);
            }
        }
        return $diff;
    }

    public function unlinkNodeWithSubtree($organization, $program)
    {
        if (!$program->children->isEmpty()) {
            foreach ($program->children as $children) {
                $children->parent_id = null;
                $children->save();
                $this->unlinkNodeWithSubtree($organization, $children);
            }
        }
        $program->parent_id = null;
        $program->save();
        cache()->forget(Program::CACHE_FULL_HIERARCHY_NAME);
    }

    public function unlinkNode($organization, $program)
    {
        $parent_id = $program->parent ? $program->parent->id : null;
        if (!$program->children->isEmpty()) {
            foreach ($program->children as $children) {
                $children->parent_id = $parent_id;
                $children->save();
            }
        }
        $program->parent_id = null;
        $program->save();
        cache()->forget(Program::CACHE_FULL_HIERARCHY_NAME);
    }

    public function listAvailableProgramsToAdd($organization, $domain)
    {
        $keyword = request()->get('keyword');
        if (!$domain->programs->isEmpty()) {
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
            $query = Program::treeOf($constraint)->withOrganization($organization);
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

    public function isFeeAccountExists($program)
    {
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
            $result = DB::select(DB::raw($qry_statement), array(
                'journal_event_type' => 'Charge setup fee to program',
                'program_account_holder_id' => $program->account_holder_id
            ));
        } catch (\Exception $e) {
            throw new \RuntimeException('Could not get fee account information in  ProgramService:isFeeAccountExists. DB query failed.', 500);
        }

        if (sizeof($result) > 0) return true;
        return false;
    }

    public function getTransferMonies(Program $program)
    {
        return (new \App\Services\Program\TransferMoniesService)->getTransferMoniesByProgram($program);
    }

    public function submitTransferMonies(Program $program, $data)
    {
        return (new \App\Services\Program\TransferMoniesService)->submitTransferMonies($program, $data);
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
        if ($program->programIsInvoiceForAwards()) {
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

            $transaction_fee = (new \App\Services\ProgramsTransactionFeeService)->calculateTransactionFee($program, $amount);
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
    public function getBillableDescendants(Program $program): array
    {
        $descendants = $program->descendants()->get();
        $billable_programs = [];
        $programs_to_skip = [];
        foreach ($descendants as $subProgram) {
            $rank = explode(".", $subProgram->path);
            $b_Skip_This = false;
            if (count($programs_to_skip) > 0) {
                foreach ($programs_to_skip as $program_to_skip) {
                    if (in_array($program_to_skip, $rank)) {
                        $b_Skip_This = true;
                    }
                }
            }
            if (!$subProgram->bill_parent_program && !$b_Skip_This) {
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
        cache()->forget(Program::CACHE_FULL_HIERARCHY_NAME);
        return $program->update(['status_id' => $validated['program_status_id']]);
    }

    public function getTemplate(Program $program)
    {
        return $this->programTemplateService->getTemplate($program);
    }

    /**
     * @param Collection $programsAward
     * @param string $demoStart
     * @return void
     */
    public function deleteAwards(Collection $programsAward, string $demoStart)
    {
        foreach ($programsAward as $awardData) {
            $awardJournalEventId = (int) $awardData->journal_event_id;
            $primeAccountHolderId = (int) $awardData->recipient_id;
            $journalEventTypeId = JournalEventType::getIdByType(JournalEventType::JOURNAL_EVENT_TYPES_REDEEM_POINTS_FOR_GIFT_CODES);
            $journalEvents = JournalEvent::getByPrimeAndEventType($primeAccountHolderId, $journalEventTypeId, $demoStart);

            if ($journalEvents) {
                foreach ($journalEvents as $journalEvent) {
                    $journalEventID = (int) $journalEvent->id;
                    $postings = Posting::where('journal_event_id', $journalEventID)->get();
                    $postingIds = $postings->pluck('id');
                    $mediumInfoIds = $postings->pluck('medium_info_id');

                    Posting::whereIn('id', $postingIds)->delete();
                    Giftcode::whereIn('id', $mediumInfoIds)->delete();
                }
            }

            $journalEvent = JournalEvent::where('id', $awardJournalEventId)->first();
            $journalEventID = $awardJournalEventId;
            $eventXmlDataId = $journalEvent ? (int)$journalEvent->event_xml_data_id : null;

            $postings = Posting::where('journal_event_id', $journalEventID)->get();
            $postingIds = $postings->pluck('id');
            $postingIds[] = (int)$awardData->posting_id;
            Posting::whereIn('id', $postingIds)->delete();

            JournalEvent::where('id', $awardJournalEventId)->delete();
            EventXmlData::where('id', $eventXmlDataId)->delete();
        }
    }

    public function getTransferTemplateCSV(Program $program)
    {
        $transferMoniesService = app('App\Services\Program\TransferMoniesService');
        return $transferMoniesService->getTransferTemplateCSVStream($program);
    }

    public function getRootPogramId($subProgramId)
    {
        $maxDepthProgram = DB::select(
            DB::raw("
            WITH RECURSIVE ParentProgram AS (
                SELECT id, parent_id, 0 AS depth
                FROM programs
                WHERE id = :program_id
                UNION ALL
                SELECT p.id, p.parent_id, pp.depth + 1 AS depth
                FROM programs p
                         INNER JOIN ParentProgram pp ON pp.parent_id = p.id
            )
            SELECT id
            FROM ParentProgram
            ORDER BY depth DESC
            LIMIT 1;
    "),
            ['program_id' => $subProgramId]
        );

        return $maxDepthProgram[0]->id ?? null;
    }
}
