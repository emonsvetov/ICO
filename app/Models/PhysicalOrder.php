<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhysicalOrder extends Model
{
	protected $guarded = [];
    protected $table = 'physical_orders';

	public static function create( $user_id, $program_id, $address, $notes = '' ) {
		$pending = Status::get_order_pending_status();
		return self::insertGetId(
			[
				'ship_to_name' => $address->ship_to_name,
				'line_1' => $address->line_1,
				'line_2' => $address->line_2,
				'zip' => $address->zip,
				'city' => $address->city,
				'user_id' => $address->user_id,
				'country_id' => $address->country_id,
				'state_id' => $address->state_id,
				'state_type_id' => $pending,
				'program_id' => $program_id,
				'notes' => $notes,
				'modified_by' => $address->user_id,
				'created_at' => date("Y-m-d H:i:s"),
				'updated_at' => date("Y-m-d H:i:s"),
			]
		);
	}
	public static function add_line_item($reserved_code_id, $physical_order_id) {
		return OrderLineItem::insertGetId(
			[
				'medium_info_id' => $reserved_code_id,
				'physical_order_id' => $physical_order_id
			]
		);
	}
}
