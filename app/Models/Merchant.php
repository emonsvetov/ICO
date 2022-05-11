<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Models\AccountHolder;
use App\Models\FinanceType;
use App\Models\MediumType;
use App\Models\Account;

class Merchant extends Model
{
    use HasFactory;
    use SoftDeletes;
  
    protected $guarded = [];

    public function children()
    {
        return $this->hasMany(Merchant::class, 'parent_id')->with('children');
    }

    public function optimal_values()
    {
        return $this->hasMany(OptimalValue::class);
    }

    public function programs()
    {
        return $this->belongsToMany(Program::class, 'program_merchant');
    }

    public function getGiftcodes( $merchant ) {
        if( is_int($merchant) ) {
            $merchant = self::find($merchant);
        }
        if(gettype($merchant) != 'object') return;
        return Giftcode::ReadListRedeemableDenominationsByMerchant( $merchant );
    }

    public function createAccount( $data )    {
        $merchant_account_holder_id = AccountHolder::insertGetId(['context'=>'Merchant', 'created_at' => now()]);
        $merchant = parent::create($data + ['account_holder_id' => $merchant_account_holder_id]);

        $asset = FinanceType::getIdByName('Asset', true);
        $monies_mt = MediumType::getIdByName('Monies', true);
        $default_accounts = array (
            array (
                    'account_type' => 'Gift Codes Available',
                    'finance_type' => $asset,
                    'medium_type' => $gift_codes_mt 
            ),
            array (
                    'account_type' => 'Monies Due to Owner',
                    'finance_type' => $asset,
                    'medium_type' => $gift_codes_mt 
            ),
            array (
                    'account_type' => 'Gift Codes Available',
                    'finance_type' => $asset,
                    'medium_type' => $gift_codes_mt 
            ) 
        );

        Account::create_multi_accounts ( $merchant_account_holder_id, $default_accounts );
        return $merchant;
    }
}
