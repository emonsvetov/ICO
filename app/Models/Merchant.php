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
    const MEDIA_SERVER = 'https://qa-api.dev.incentco.net';

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
        if( count($program->merchants) )    {
            return $program->merchants;
        }
        //TODO - If there is no merchants for this program the we need to check whether this is a subprogram, and hence get list of merchants by parent program id
        // if (Program::is_sub_program ( $program_account_holder_id )) {
        //     $parent_id = Program::read_parent_account_holder_id ( $program_account_holder_id );
        //     return self::read_list_by_program ( $parent_id, $offset, $limit );
        // }

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

}
