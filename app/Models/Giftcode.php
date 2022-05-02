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

	static function ReadListRedeemableDenominationsByMerchant($merchant, $where_column = '', $where_value = 0, $end_date = '') {

        $merchant_id = self::extractId($merchant);

		$where_value = ( float ) $where_value;
		$allowed_columns = array (
				'sku_value',
				'redemption_value' 
		);
		if ($where_column != '') {
			if (! in_array ( $where_column, $allowed_columns )) {
				throw new InvalidArgumentException ( 'Invalid "where_column" passed, must be one of these "' . implode ( ', ', $allowed_columns ) . '"', 400 );
			}
			// verify that $limit is valid and greater than 0
			if (! is_float ( $where_value ) || $where_value < 0) {
				throw new InvalidArgumentException ( 'Invalid "where_value" passed, must be a float and value is >= 0', 400 );
			}
		}
		if ($end_date != '') {
			if (validate_date ( $end_date, "Y-m-d" ) == false) {
				throw new UnexpectedValueException ( 'Invalid "end_date" passed, format should be "Y-m-d"', 400 );
			}
		}
		if ($merchant->get_gift_codes_from_root) {
			$query_merchant_id = Merchant::get_top_level_merchant_id ( $merchant_id );
		}	else {
			$query_merchant_id = $merchant_id;
		}
		// construct the SQL statement to query available gift codes
		// gift codes debit count must be greater than credit count which would determine for gift codes that has already been redeemed or gift codes that was redeemed but cancelled
		$sql = "SELECT 
					'{$merchant_id}' as merchant_id,
                    `account_holder_id` as top_level_merchant_id,
                    `redemption_value`,
                    `sku_value`,
                    `redemption_value` - `sku_value` as `redemption_fee`,
                        COUNT(DISTINCT medium_info.`id`) as count
                    from
                        medium_info 
                        join postings on medium_info.id = postings.medium_info_id
                        join accounts a on postings.account_id = a.id
                    where   
                        account_holder_id = {$query_merchant_id}";
		if ($end_date != '') {
			$sql .= " AND purchase_date <= '{$end_date}' AND (redemption_date is null OR redemption_date > '{$end_date}') ";
		} else {
			$sql .= " AND redemption_date is null ";
		}
		$sql .= " AND 
                        `hold_until` <= now()";
		if ($where_column != '') {
			if (is_float ( $where_value ) && $where_value != 0) {
				$sql .= " AND `{$where_column}` = {$where_value}";
			}
		}
		$sql .= " group by
                        sku_value, redemption_value 
                    
                ";
		// add order by
		$sql .= "
                    ORDER BY
                    `sku_value`, `redemption_value` ASC";
		// execute the SQL to get the results
		try {
			// echo $sql;exit;
			$result = DB::select ( DB::raw($sql) );
		} catch (Exception $e) {
			throw new RuntimeException ( 'SQL statement to query available gift codes in GiftCode::read_list_redeemable_denominations_by_merchant failed', 500 );
		}
		// return the result on an array of objects
		return $result;
	}
}
