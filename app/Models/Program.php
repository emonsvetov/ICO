<?php

namespace App\Models;

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
        return $this->hasMany(Program::class, 'parent_id')->with('children');
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

    public function program_is_invoice_for_awards( $extraArg = false) {
		if ($this->invoice_for_awards == 1) {
			return true;
		}
        if($extraArg)   {
            if ( $this->factor_valuation != 1 ) {
                return true;
            }
        }
		return false;
	}
    public function createAccount( $data )    {
        $program_account_holder_id = AccountHolder::insertGetId(['context'=>'Program', 'created_at' => now()]);
        if(isset($data['invoice_for_awards']) && $data['invoice_for_awards'])   {
            $data['allow_creditcard_deposits'] = 1;
        }
        if(!isset($data['expiration_rule_id']))   {
            $data['expiration_rule_id'] = 3; //End of Next Year
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

        $program->create_setup_fee();

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
        $owner_account_holder_id = Owner::find(1)->account_holder_id;
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
    
    public function programIsInvoiceForAwards(): bool
    {
        if ($this->invoice_for_awards || $this->factor_valuation != 1) {
            return true;
        }
        return false;
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
}
