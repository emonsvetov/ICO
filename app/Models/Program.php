<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\AccountHolder;
use App\Models\FinanceType;
use App\Models\MediumType;

class Program extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $guarded = [];

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    public function children()
    {
        return $this->hasMany(Program::class, 'program_id')->with('children');
    }

    public function events()
    {
        return $this->hasMany(Event::class);
    }

    public function merchants()
    {
        return $this->belongsToMany(Merchant::class, 'program_merchant')
        ->withPivot('featured', 'cost_to_program')->withTimestamps();
    }

    public function users()
    {
        return $this->belongsToMany(User::class, 'program_user')
        ->withTimestamps();
    }

    public function program_is_invoice_for_awards() {
		if ($this->invoice_for_awards == 1) {
			return true;
		}
        if ( $this->factor_valuation != 1 ) {
            return true;
        }
		return false;
	}
    public function createAccount( $data )    {
        $program_account_holder_id = AccountHolder::insertGetId(['context'=>'Program', 'created_at' => now()]);
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

        $default_participant_role_id = Role::getIdByNameAndOrg("Participant", $program->organization_id);

        return $program;
    }
}
