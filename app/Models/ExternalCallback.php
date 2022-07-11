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
		return self::selectRaw(
			"external_callbacks.*,
			t.type as callback_type
			"
		)
		->join('callback_types AS t', 'external_callbacks.callback_type_id', '=', 't.id')
		->where('external_callbacks.account_holder_id', $account_holder_id)
		->where('t.type', '=', $callback_type)
		->get();
	}
}
