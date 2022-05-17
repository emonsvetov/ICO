<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DB;

class ExternalCallback extends Model
{
	use HasFactory;
    protected $guarded = [];

	public static function read_list_by_type($account_holder_id, $callback_type) {

		DB::statement("SET SQL_MODE=''"); //SQLSTATE[42000] fix!
		DB::enableQueryLog();
		return self::selectRaw(
			"external_callbacks.*,
			t.type as callback_type
			"
		)
		->join('callback_types AS t', 'external_callbacks.callback_type_id', '=', 't.id')
		->where('external_callbacks.account_holder_id', $account_holder_id)
		->where('t.type', '=', $callback_type)
		->get();


		// $sql = "SELECT c.*, t.type as callback_type
        //     from " . Incentco::TBL_EXTERNAL_CALLBACKS . " c
        //         JOIN " . Incentco::TBL_CALLBACKS_TYPES . " t on (c.callback_type_id = t.id)
        //     where
        //         account_holder_id = :account_holder_id
        //     and
        //         t.type = :type
        // ";
		// try {
		// 	$results = DB::select( DB::raw($sql), array(
		// 		'account_holder_id' => $account_holder_id,
		// 		'type' => $callback_type,

		// 	));
		// } catch (Exception $e) {
		// 	throw new Exception ( 'Could not "read_list_by_type" in ExternalCallback:read_list_by_type. DB query failed.', 400 );
		// }
		// return $results;
	}
}
