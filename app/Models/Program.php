<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
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

class Program extends BaseModel
{
    use HasFactory;
    use SoftDeletes;
    use WithOrganizationScope;
    use Treeable;
    use HasRecursiveRelationships;

    protected $guarded = [];

    const STATUS_ACTIVE = 'Active';
    const STATUS_DELETED = 'Deleted';
    const STATUS_LOCKED = 'Locked';

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

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function unit_numbers()
    {
        return $this->hasMany(UnitNumber::class);
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

    public function users()
    {
        return $this->belongsToMany(User::class, 'program_user')
        ->withTimestamps();
    }

    public function template()
    {
        return $this->hasOne(ProgramTemplate::class);
    }

    public function programIsInvoiceForAwards(): bool
    {
        if ($this->invoice_for_awards || $this->factor_valuation != 1) {
            return true;
        }
        return false;
    }

    /**
     * Alias to "programIsInvoiceForAwards"
     * Param - $extraArg boolean
     */

    public function program_is_invoice_for_awards( $extraArg = false) {
        return $this->programIsInvoiceForAwards();
		// if ($this->invoice_for_awards == 1) {
		// 	return true;
		// }
        // if($extraArg)   {
        //     if ( $this->factor_valuation != 1 ) {
        //         return true;
        //     }
        // }
		// return false;
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

    public static function read_programs($programIds, $with_rank = false)  {
        if( !$programIds ) return;
        if( $with_rank )    {
            //TODO
        }
        return self::whereIn('id', $programIds)->get();
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
        $excludeStatus = [
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

    private function getTemplateRecursive( $program )
    {
        if( $program->template ) return $program->template;

        if( $program->parent()->exists() )
        {
            $parent = $program->parent()->first();
            if( $parent->template ) {
                return $parent->template;
            } else {
                return $parent->getTemplateRecursive( $parent );
            }
        }
    }

    public function load( $relations )
    {
        $template_key = 'template';

        if( (is_string($relations) && $relations == $template_key) || (is_array($relations) && sizeof($relations) > 0 && in_array($template_key, $relations)  ))
        {
            $this->template = $this->getTemplateRecursive($this);

            if(is_array($relations))
            {
                $key = array_search($template_key, $relations);
                unset($relations[$key]);
            }
            if(is_string($relations))
            {
                $relations = [];
            }
        }

        return parent::load( $relations );
    }
}
