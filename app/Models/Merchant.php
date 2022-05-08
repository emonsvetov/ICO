<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Models\AccountHolder;

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
        $account_holder_id = AccountHolder::insertGetId(['context'=>'Merchant', 'created_at' => now()]);
        return parent::create($data + ['account_holder_id' => $account_holder_id]);
    }
}
