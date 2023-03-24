<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use DateTime;
class ExpirationRule extends Model
{
    use HasFactory;
    public static function compile(ExpirationRule $expirationRule, $start_date, $specified, $custom_offset, $custom_units, $annual_month, $annual_day) {
		$sql = "select ". self::getExpirationDateSql($expirationRule, $start_date, $specified, $custom_offset, $custom_units, $annual_month, $annual_day)." as expires";
        try {
            $results = DB::select( DB::raw($sql));
            if (sizeof($results) < 1 ) {
                throw new RuntimeException ( "Failed to compile expiration rule ({$expirationRule->name}): {$sql}" );
            }
        } catch (\Exception $e) {
            throw new \Exception ( 'Could not get expiration rules. DB query failed with error:' . $e->getMessage(), 400 );
        }
		return $results[0]->expires;
	}

    //Alias for get_expiration_date_sql
    public static function getExpirationDateSql(ExpirationRule $expirationRule, $start_date, $specified, $custom_offset, $custom_units, $annual_month, $annual_day) {
		// use the end date of the active goal plan and the expiration rule to set the end date for the future goal goal
		switch ($expirationRule->name) {
			case "Annual" :
				// use the annual month and day parameters
				// $end_date_sql= "date_add({$start_date}, interval {$custom_offset} {$custom_units})";
				$end_date_sql = date ( "'Y-{$annual_month}-{$annual_day}'", strtotime ( '+1 year' ) );
				$end_date_sql =$end_date_sql;
				break;
			case "Custom" :
				// the offset and units are parameters to this function
				$end_date_sql = "date_add('{$start_date}', interval {$custom_offset} {$custom_units})";
				break;
			case "Specified" :
				// Need to do some math to figure out how many days are between the active goal plans start and end dates
				if ($specified === null) {
					// $specified= $this->speculate_next_specified_end_date($start_date, $end_date);
					throw new RuntimeException ( "Invalid Specified End Date: '{$specified}'", 500 );
				}
				$date1 = new DateTime ( $start_date );
				$date2 = new DateTime ( $specified );
				// $diff_in_days = "(unix_timestamp('2014-07-10') - unix_timestamp('2014-07-05')) / (60 * 60 * 24))";
				$diff_in_days = $date2->diff ( $date1 )->format ( "%a" );
				$end_date_sql = "date_add('{$start_date}', interval {$diff_in_days} DAY)";
				break;
			case "End of Following Year" :
			case "End of Next Year" :
			case "1 Year" :
				if (! isset ( $start_date ) || trim ( $start_date ) == '') {
					$end_date_sql = date ( 'Y-12-31', strtotime ( '+1 year' ) );
				} else {
					$end_date_sql = date ( "Y-12-31", strtotime ( date ( "Y-m-d", strtotime ( $start_date ) ) . " +1 year" ) );
				}
				$end_date_sql ="'".$end_date_sql."'";
				break;
			case "12 Months" :
			case "9 Months" :
			case "6 Months" :
			case "3 Months" :
			default :
				$offset = $expirationRule->expire_offset;
				$units = $expirationRule->expire_units;
				$end_date_sql = "date_add('{$start_date}', interval {$offset} {$units})";
		}
		return $end_date_sql;
	}
	public static function getExpirationRule($id)
    {
        $expirationRule = self::find($id);
        return $expirationRule; 
    }
	//Aliases for speculate_next_specified_end_date
	public static function speculateNextSpecifiedEndDate($start_date, $end_date) {
		// Need to do some math to figure out how many days are between the active goal plans start and end dates
		if ($start_date == null) {
			return null;
		}
		if ($end_date === null) {
			return null;
		}
		$date1 = new DateTime ( $start_date );
		$date2 = new DateTime ( $end_date );
		$diff_in_days = $date2->diff ( $date1 )->format ( "%a" );
		$end_date_sql = "date_add({$end_date}, interval {$diff_in_days} DAY)";
		$results = DB::select( DB::raw("select {$end_date_sql} as expires"));
		return $results[0]->expires;
	
	}
}