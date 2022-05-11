<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\IdExtractor;
use Carbon\Carbon;
use DB;

class Giftcode extends Model
{
    use HasFactory, IdExtractor;

    protected $guarded = [];
    protected $table = 'medium_info';

    public function setPurchaseDateAttribute($purchaseDate)
    {   
        $this->attributes['purchase_date'] = Carbon::createFromFormat('d/m/Y', $purchaseDate)->format('Y-m-d');
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

	public function getRedeemableListByMerchant($merchant, $filters = []) {

		DB::statement("SET SQL_MODE=''"); //SQLSTATE[42000] fix!
		// DB::enableQueryLog();
		return self::selectRaw(
			"'{$merchant->id}' as merchant_id,
			'{$merchant->account_holder_id}' as merchant_account_holder_id,
			a.`account_holder_id` as top_level_merchant_id, 
			`redemption_value`
			`sku_value`,
			`redemption_value` - `sku_value` as `redemption_fee`,
			COUNT(DISTINCT medium_info.`id`) as count"
		)
		->join('postings', 'postings.medium_info_id', '=', 'medium_info.id')
		->join('accounts AS a', 'postings.account_id', '=', 'a.id')
		->groupBy('sku_value')
		->groupBy('redemption_value')
		->orderBy('sku_value')
		->orderBy('redemption_value')
		->where('a.account_holder_id', $merchant->account_holder_id)
		->where('medium_info.hold_until', '<=', now())
		->get();
	}
}
