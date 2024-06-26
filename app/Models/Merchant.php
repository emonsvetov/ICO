<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\Treeable;
use App\Models\AccountHolder;
use App\Models\FinanceType;
use App\Models\MediumType;
use App\Models\Account;
use Staudenmeir\LaravelAdjacencyList\Eloquent\HasRecursiveRelationships;

class Merchant extends Model
{
    use HasFactory;
    use SoftDeletes;
    use Treeable;
    use HasRecursiveRelationships;

    protected $guarded = [];

    const MEDIA_FIELDS = ['logo', 'icon', 'large_icon', 'banner'];

    const ACTIVE = 1;

    public function findByIds($ids = [])
    {
         //not sure whether to get with tree, if so, uncomment next line and the query block with "children"
        // $query = $this->where('parent_id', null);
        $query = $this;
        if( is_array( $ids ) && count( $ids ) > 0 )
        {
            $query = $query->whereIn( 'id',  $ids);
        }
        // return $query->select('id','name','parent_id')
        // ->with(['children' => function($q1) {
        //     return $q1->select('id','name','parent_id')
        //     ->with(['children' => function($q2){
        //         return $q2->select('id','name','parent_id');
        //     }]);
        // }])->get();
        return $query->get();
    }

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

    public function tangoOrdersApi()
    {
        return $this->belongsTo(TangoOrdersApi::class, 'toa_id');
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
        $gift_codes_mt = MediumType::getIdByName('Gift Codes', true);
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

    public static function readListByProgram( $program ) {
        $merchants = $program->getMerchantsRecursively();
        if ($merchants->isNotEmpty()) {
            return $merchants;
        }
        return [];
    }

    public static function get_top_level_merchant( $merchant )    {
        if(is_numeric($merchant))   {
            $merchant = self::find($merchant);
        }
        return $merchant->getRoot();
    }

    public static function getTree(): Collection
    {
        return self::with('children')
            ->whereNull('parent_id')
            ->get();
    }

    public static function getFlatTree(): Collection
    {
        return self::tree()->depthFirst()->get();
    }

    public static function getByMerchantCode($code)
    {
        return self::where('merchant_code', $code)->first();
    }

    public function inventoryCount($propertyName = 'available_giftcode_count', array $args = [])
    {
        $this->{$propertyName} = (new \App\Services\GiftcodeService)->getInventoryCountByMerchant($this, $args);
    }

    public function redeemedCount($propertyName = 'redeemed_giftcode_count', array $args = [])
    {
        $this->{$propertyName} = (new \App\Services\GiftcodeService)->getRedeemedCountByMerchant($this, $args);
    }

    public static function notInHierarchy(Merchant $merchant)
    {
        $query = self::whereNull('parent_id');
        $query->where('id', '<>', $merchant->id);

        return $query->doesntHave('children')->get();
    }

    public static function inHierarchy(Merchant $merchant)
    {
        $query = self::where('parent_id', $merchant->id);

        return $query->select('id', 'name')->with(['children' => function($query){
            return $query->select('id', 'name', 'parent_id');
        }])->get();
    }

}
