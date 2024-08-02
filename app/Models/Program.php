<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;
use \Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\Traits\WithOrganizationScope;
use App\Models\JournalEventType;
use App\Models\Traits\Treeable;
use App\Models\AccountHolder;
use App\Models\JournalEvent;
use App\Models\FinanceType;
use App\Models\MediumType;
use App\Models\BaseModel;
use App\Models\Currency;
use App\Models\Account;
use App\Models\Owner;
use App\Models\ProgramReports;

/**
 * @property int $account_holder_id
 * @property int $factor_valuation
 * @property bool $is_demo
 * @property timestamp $created_at
 */
class Program extends BaseModel
{
    use HasFactory;
    use SoftDeletes;
    use WithOrganizationScope;
    use Treeable;
    use HasRecursiveRelationships;

    // public $table = 'programs_live';

    protected $guarded = [];

    const STATUS_ACTIVE = 'Active';
    const STATUS_DELETED = 'Deleted';
    const STATUS_LOCKED = 'Locked';
    const MIN_FIELDS = ['id', 'name', 'parent_id', 'account_holder_id','use_budget_cascading','use_cascading_approvals'];
    const CACHE_FULL_HIERARCHY_NAME = 'hierarchy_list_of_all_programs';

    public function resolveSoftDeletableRouteBinding($value, $field = null)
    {
        return parent::resolveSoftDeletableRouteBinding($value, $field);
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function parent()
    {
        return $this->belongsTo(Program::class, 'parent_id');
    }

    public function children()
    {
        return $this->hasMany(Program::class, 'parent_id')->with(['children', 'status']);
    }

    public function childrenMinimal()
    {
        return $this->hasMany(Program::class, 'parent_id')->select(self::MIN_FIELDS)->with(['childrenMinimal', 'status']);
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function unit_numbers()
    {
        return $this->hasMany(UnitNumber::class);
    }

    public function position_levels()
    {
        return $this->hasMany(PositionLevel::class);
    }

    public function budget_programs()
    {
        return $this->hasMany(BudgetProgram::class);
    }

    public function budgets_cascading()
    {
        return $this->hasMany(BudgetCascading::class);
    }

    public function address()
    {
        return $this->hasOne(Address::class, 'account_holder_id', 'account_holder_id')->with(['state', 'country']);
    }

    public function merchants()
    {
        return $this->belongsToMany(Merchant::class, 'program_merchant')
        ->withPivot('featured', 'cost_to_program')->withTimestamps();
    }

    public function domains()
    {
        return $this->belongsToMany(Domain::class, 'domain_program')->withTimestamps();
    }

    public function defaultDomain()
    {
        return $this->belongsToMany(Domain::class, 'domain_program')
            ->wherePivotIn('default', [1])->withTimestamps();
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'program_user')
        ->withTimestamps();
    }

    public function templates()
    {
        return $this->hasMany(ProgramTemplate::class);
    }

    public function template()
    {
        return $this->hasOne(ProgramTemplate::class)->ofMany('is_active', 'max');
    }

    public function ledger_codes()
    {
        return $this->hasMany(EventLedgerCode::class);
    }

    public function programExtras()
    {
        return $this->hasOne(ProgramExtra::class);
    }

    public function programTransactionFee()
    {
        return $this->hasMany(ProgramTransactionFee::class);
    }

    public function programIsInvoiceForAwards($extraArg = false): bool
    {
        if ($this->invoice_for_awards || ($extraArg && $this->factor_valuation != 1)) {
            return true;
        }
        return false;
    }

    /**
     * Alias to "programIsInvoiceForAwards"
     * Param - $extraArg boolean
     */

    public function program_is_invoice_for_awards( $extraArg = false): bool {
        return $this->programIsInvoiceForAwards($extraArg);
	}

    public function is_invoice_for_awards( $extraArg = false): bool {
        return $this->programIsInvoiceForAwards($extraArg);
	}

    public static function createAccount( $data )    {

        $program_account_holder_id = AccountHolder::insertGetId(['context'=>'Program', 'created_at' => now()]);

        if(isset($data['invoice_for_awards']) && $data['invoice_for_awards'])   {
            $data['allow_creditcard_deposits'] = 1;
        }
        if(!isset($data['expiration_rule_id']))   {
            $data['expiration_rule_id'] = 3; //End of Next Year
        }
        if (!empty($data['status'])) { //If status present in string format
            $data['status_id'] = !empty($data['status_id']) ? $data['status_id'] : self::getStatusIdByName($data['status']);
            unset($data['status']);
        }
        if( empty($data['status_id']) )
        {   //set default status to "Active"
            $data['status_id'] = self::getIdStatusActive();
        }
        $program = parent::create($data + ['account_holder_id' => $program_account_holder_id]);
        $liability = FinanceType::getIdByName('Liability');
        $asset = FinanceType::getIdByName('Asset', true);
        $monies_mt = MediumType::getIdByName('Monies', true);
        $default_accounts = array (
            array (
                    'account_type' => 'Monies Deposits',
                    'finance_type' => $liability,
                    'medium_type' => $monies_mt
            ),
            array (
                    'account_type' => 'Monies Due to Owner',
                    'finance_type' => $asset,
                    'medium_type' => $monies_mt
            ),
            array (
                    'account_type' => 'Monies Fees',
                    'finance_type' => $liability,
                    'medium_type' => $monies_mt
            ),
            array (
                    'account_type' => 'Monies Paid to Progam',
                    'finance_type' => $liability,
                    'medium_type' => $monies_mt
            ),
            array (
                    'account_type' => 'Monies Redeemed',
                    'finance_type' => $liability,
                    'medium_type' => $monies_mt
            ),
            array (
                    'account_type' => 'Monies Shared',
                    'finance_type' => $liability,
                    'medium_type' => $monies_mt
            ),
            array (
                    'account_type' => 'Monies Transaction',
                    'finance_type' => $liability,
                    'medium_type' => $monies_mt
            )
        );

        Account::create_multi_accounts ( $program_account_holder_id, $default_accounts );

        //TODO ??
        // $this->tie_sub_program ( $program_account_holder_id, $program_account_holder_id );

        // $default_participant_role_id = Role::getIdByNameAndOrg("Participant", $program->organization_id);

        // $this->award_levels_model->create ( $program_account_holder_id, 'default' );

        $program->create_setup_fee_account();

        return $program;
    }

    public static function read_programs(array $programAccountHolderIds = [], bool $with_rank = false, $offset = 0, $limit =99999)  {
        if( !$programAccountHolderIds ) return;
        // pr($programAccountHolderIds);
        if( $with_rank )    {
            $programs = (new Program)->whereIn('account_holder_id', $programAccountHolderIds)->get()->toTree();
            $programs = _tree_flatten($programs);
            return $programs;
        }
        $programs = self::whereIn('account_holder_id', $programAccountHolderIds)->offset((int)$offset)->limit((int)$limit)->get()->toTree();
        $programs = _tree_flatten($programs);
        return $programs;
    }

    public function create_setup_fee_account()   {
        $setup_fee = $this->setup_fee;
        if( !is_numeric($setup_fee) || $setup_fee <=0 ) return; //NOT SURE IF SHOULD CREATE
        // $owner_account_holder_id = Owner::find(1)->account_holder_id; //$owner_account_holder_id is passed to the sp_journal_program_charge_for_setup_fee in current system but not used!
        $program_account_holder_id = $this->account_holder_id;
        $currency_id = Currency::getIdByType(config('global.default_currency'), true);
        $journal_event_type_id = JournalEventType::getIdByType( "Charge setup fee to program", true );
        // 25 - Charge setup fee to program
        $journal_event_id = JournalEvent::insertGetId([
			'journal_event_type_id' => $journal_event_type_id,
			'created_at' => now()
		]);
        $monies = MediumType::getIdByName('Monies', true);
        $asset = FinanceType::getIdByName('Asset', true);
        $liability = FinanceType::getIdByName('Liability', true);
        Account::postings(
            $program_account_holder_id,
            'Monies Due to Owner',
            $asset,
            $monies,
            $program_account_holder_id,
            'Monies Fees',
            $liability,
            $monies,
            $journal_event_id,
            $setup_fee,
            1, //qty
            null, // medium_info
            null, // medium_info_id
            $currency_id
        );
    }

    public function isShellProgram(): bool
    {
        return $this->type == config('global.program_type_shell');
    }

    public function getManagers( $count = false )
    {
        $includeStatus = [
            'Active',
            'Pending Deactivation',
            'Locked'
        ];

        $query = User::whereHas('roles', function ($query) {
            $query->where('roles.name', 'LIKE', config('roles.manager'))
            ->where('model_has_roles.program_id', $this->id);
        })
        ->join('statuses', 'statuses.id', '=', 'users.user_status_id')
        ->where('statuses.context', '=',  'Users')
        ->where(function ($query) use($includeStatus) {
            for ($i = 0; $i < count($includeStatus); $i++){
               $query->orwhere('statuses.status', 'LIKE',  $includeStatus[$i]);
            }
       });
       if( $count ) {
        return $query->count();
       }
       return $query->get();
    }

    public function getBillableParticipants( $count = false, $since = '1970-01-01')
    {
        $excludeStatus = [
            'Pending Activation',
            'Active',
            'Pending Deactivation',
            'Locked'
        ];

        $query = User::whereHas('roles', function ($query) {
            $query->where('name', 'LIKE', config('roles.participant'))
            ->where('model_has_roles.program_id', $this->id);
        })
        ->join('statuses', 'statuses.id', '=', 'users.user_status_id')
        ->where('statuses.context', '=',  'Users')
        ->where('users.created_at', '>=',  $since)
        ->where(function ($query) use($excludeStatus) {
            for ($i = 0; $i < count($excludeStatus); $i++){
               $query->orwhere('statuses.status', '=',  $excludeStatus[$i]);
            }
       });
       if( $count ) {
        return $query->count();
       }
       return $query->get();
    }

    /**
     * Get Domain for a program;
     * If no domain found for a program, then try to get parent program's domain;
     * TODO: Discuss "get domain" approach with team.
     */
    public function getDomain()
    {
        if( $this->domains->isNotEmpty())   {
            return $this->domains->first();
        }
        if($this->parent()->exists())
        {
            $parent = $this->parent()->first();
            return $parent->getDomain();
        }
        return null;
    }

    /**
     * Get Domain Host for a program;
     * If no domain found for a program, then try to get parent program's domain;
     * TODO: Discuss "get domain" approach with team.
     */
    public function getHost()
    {
        $domain = $this->getDomain();
        if( $domain &&  $domain->exists())
        {
            return $domain->name;
        }
        return null;
    }

    public static function getFlatTree(): Collection
    {
        return self::tree()->depthFirst()->get();
    }

    public function status()
    {
        return $this->belongsTo(Status::class, 'status_id');
    }

    public static function getStatusByName( $status ) {
        return Status::getByNameAndContext($status, 'Programs');
    }

    public static function getStatusIdByName( $status ) {
        return self::getStatusByName($status)->id;
    }

    public static function getStatusActive()
    {
        return self::getStatusByName(self::STATUS_ACTIVE);
    }

    public static function getIdStatusActive()
    {
        return self::getStatusActive()->id;
    }

    public static function getStatusDeleted()
    {
        return self::getStatusByName(self::STATUS_DELETED);
    }

    public static function getIdStatusDeleted()
    {
        return self::getStatusDeleted()->id;
    }

    public static function getStatusLocked()
    {
        return self::getStatusByName(self::STATUS_LOCKED);
    }

    public static function getIdStatusLocked()
    {
        return self::getStatusLocked()->id;
    }

    public function getTemplate()
    {
        $inheritFields = null;
        // If available return it
        if( $this->template ) {
            //Let's find whether basic fields have values; if not try to patch them with parent's theme or default theme field values
            foreach( ProgramTemplate::IMAGE_FIELDS as $imgField )    {
                if( !$this->template->{$imgField} ) {
                    $inheritFields[] = $imgField;
                }
            }
        }

        if( !$this->template ||  $inheritFields)  {
            // Get first available template from ancestors
            $ancestors = $this->ancestors()->pluck('id');
            if( $ancestors )
            {
                $ancestor = $this->has('template')->whereIn('id', $ancestors)->latest()->first();
                if( $ancestor && $ancestor->template ) {
                    if( $this->template ) {
                        if( $inheritFields )    {
                            foreach( $inheritFields as $field ) {
                                $this->template->{$field} = $ancestor->template->{$field};
                            }
                            $this->template->inherited = ['from' => $ancestor->id, 'fields' => $inheritFields];;
                        }
                    }   else {
                        $ancestor->template->inherited = ['from' => $ancestor->id, 'fields' => null];
                        $this->setRelation('template', $ancestor->template); //null means "all"
                    }
                }
            }
        }
        if( !$this->template ) {
            // If not set then use default template
            $newTemplate = new ProgramTemplate( ProgramTemplate::DEFAULT_TEMPLATE );
            $newTemplate->default = true;
            $this->setRelation('template', $newTemplate);
        }

        return $this->template;
    }
    /***
     * Alias to getTemplate()
     */
    public function loadTemplate()
    {
        return $this->getTemplate();
    }

    public function getPointsExpirationDateSql()
    {
        $end_date_sql = "date_format(date_add(postings.created_at, interval 1 year), '%Y-12-31')";
        // use the end date of the active goal plan and the expiration rule to set the end date for the future goal goal
		if ($this->expiration_rule_id == 1){
            // use the annual month and day parameters
            $end_date_sql = "date_format(date_add(postings.created_at, interval 1 year), '%Y-12-31')";
        }elseif($this->expiration_rule_id == 2){
		    $end_date_sql = "date_format(date_add(postings.created_at, interval 2 year), '%Y-12-31')";
        }else{ // custom

        }

		return $end_date_sql;
    }

    public static function getAllRootEmployeeType(){
        return self::IsRoot()->whereIn('type', ['employee'])->get();
    }

    public static function getAllRoot(){
        return self::IsRoot()->get();
    }

    public function getActiveManagers( $count = false )
    {
        $query = User::whereHas('roles', function ($query) {
            $query->where('roles.name', 'LIKE', config('roles.manager'))
                ->where('model_has_roles.program_id', $this->id);
        })
            ->join('statuses', 'statuses.id', '=', 'users.user_status_id')
            ->where('statuses.context', '=',  'Users')
            ->where('statuses.status', '=',  User::STATUS_ACTIVE);
        return $count ? $query->count() : $query->get();
    }


    public function getEmailTemplateSender()
    {
        $root = $this->getRoot();
        return $root     ? EmailTemplateSender::where('program_id', $root->id)->first() : null;
    }

    public function get_top_level_program_id($id = 0)
    {
        $program = self::where('id', $id)->first();
        if (!$program->parent_id){
            return $id;
        } else{
            return $this->get_top_level_program_id($program->parent_id);
        }

    }

    public function selected_reports()
    {
        return $this->belongsToMany(ProgramList::class, 'program_reports', 'program_id', 'report_id');
    }

    public function csv_import_types()
    {
        return $this->belongsToMany(CsvImportType::class, 'program_csv_import_types', 'program_id', 'csv_import_type_id');
    }

    public function getCsvImportypesRecursively( $onlyIds = false )
    {
        $relationExists = $this->csv_import_types()->exists();
        if($relationExists) {
            $collection = $this->csv_import_types()->get();
            if( $onlyIds ) {
                return $collection->pluck('id');
            }
            return $collection;
        }   else {
            $parent = $this->getParent();
            if( $parent ) {
                return $parent->getCsvImportypesRecursively( $onlyIds );
            }
            return [];
        }
    }

    public function hasCsvImportypes( $inherit = false )
    {
        $relationExists = $this->csv_import_types()->exists();
        if($relationExists) {
            return true;
        }
        if( $inherit ) {
            $parent = $this->getParent();
            if( $parent ) {
                return $parent->hasCsvImportypes( $inherit );
            }
        }
        return false;
    }

    public function getMerchantsRecursively($status=null, &$inheritsFrom = null)
    {
        $query = $this->merchants();
        $relationExists = $query->exists();
        if($relationExists) {
            if (!empty($status)) {
                $query = $query->where('status', $status);
            }
            $collection = $query->orderBy('name')->get();
            return $collection;
        }   else {
            $parent = $this->getParent();
            if( $parent ) {
                $inheritsFrom = $parent->only(['id', 'name']);
                return $parent->getMerchantsRecursively($status, $inheritsFrom);
            }
            return [];
        }
    }

    public function getParentProgramId($subProgramId)
    {
        $hierarchy = null;
        $program = Program::find($subProgramId);
        while ($program && $program->parent_id !== null) {
            $program = Program::find($program->parent_id);
        }
        if ($program) {
            $hierarchy = $program->id;
        }
        return $hierarchy;
    }

    public function setShownId(){
        $this->shownId = $this->v2_account_holder_id ?: $this->account_holder_id;
    }
}
