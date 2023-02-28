<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use DB;

class ExternalCallback extends Model
{
	use HasFactory;
    protected $guarded = [];

    const CALLBACK_TYPE_GOAL_MET = 'Goal Met';
    const CALLBACK_TYPE_GOAL_EXCEEDED = 'Goal Exceeded';

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
	/**
     * @param Organization $organization
     * @param Program $program
     * @param array $params
     * @return mixed
     */
    public static function getIndexData(Organization $organization, Program $program, array $params)
    {
        DB::statement("SET SQL_MODE=''"); //SQLSTATE[42000] fix!
		$query =  self::selectRaw(
			"external_callbacks.*,
			t.type as callback_type
			"
		)
		->join('callback_types AS t', 'external_callbacks.callback_type_id', '=', 't.id')
		//->where('external_callbacks.account_holder_id', $program->account_holder_id);
        ->where('external_callbacks.account_holder_id', 5000);

		if (isset($params['type'])){
            $query->where('t.type', '=', $params['type']);
        }
        return $query->get();
    }
}
