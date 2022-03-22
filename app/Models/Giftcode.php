<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use DB;

class Giftcode extends Model
{
    use HasFactory;

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

    public function read_sku_values($merchant_ids = [], $end_date = '') {

        $query = $this->select('sku_value')->groupBy('sku_value');
        if( count($merchant_ids) > 0 )  {
            $query = $query->whereIn('merchant_id', $merchant_ids);
        }
        if ($end_date != '') {
            $query->where('purchase_date', '<=', $end_date);
		}
        return $query->get();
    }

    public function read_list_redeemable_denominations_by_merchant($merchant_id = 0, $end_date = '') {
		// check to see if the merchant gets its gift codes from the its root merchant
		// if it does, query using the root merchant id instead
		$merchant = Merchant::where ( 'id', $merchant_id )->first();
		if ($merchant->get_gift_codes_from_root) {
			$merchant_id = $this->merchant->get_top_level_merchant_id ( $merchant_id );
		}
        $params = [
            'merchant_id' => $merchant_id
        ];
		// construct the SQL statement to query available gift codes
		// gift codes debit count must be greater than credit count which would determine for gift codes that has already been redeemed or gift codes that was redeemed but cancelled
		$sql = "SELECT 
                    `merchant_id`,
                    `redemption_value`,
                    `sku_value`,
                    `redemption_value` - `sku_value` as `redemption_fee`,
                        COUNT(DISTINCT medium_info.`id`) as count
                    from
                        medium_info
                    where   
                        merchant_id = :merchant_id";
		if ($end_date != '') {
			$sql .= " AND purchase_date <= :end_date AND (redemption_date is null OR redemption_date > :end_date_1) ";
            $params['end_date'] = $end_date;
            $params['end_date_1'] = $end_date;
		} else {
			$sql .= " AND redemption_date is null ";
		}
		$sql .= " AND 
                        `hold_until` <= now()";
		$sql .= " group by
                        sku_value, redemption_value, merchant_id
        ";
		// add order by
		$sql .= "
                    ORDER BY
                    `sku_value`, `redemption_value` ASC";
		// execute the SQL to get the results

        try {
            $results = DB::select( DB::raw($sql), $params);
        } catch (Exception $e) {
            throw new Exception ( 'Could not get codes. DB query failed with error:' . $e->getMessage(), 400 );
        }
		return $results;
	}
}
