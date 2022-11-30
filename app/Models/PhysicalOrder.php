<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PhysicalOrder extends Model
{
	protected $guarded = [];
    protected $table = 'physical_orders';

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

	public static function create( $user_id, $program_id, $address, $notes = '' ) {
		$pending = Status::get_order_pending_status();
		return self::insertGetId(
			[
				'ship_to_name' => $address->ship_to_name,
				'line_1' => $address->line_1,
				'line_2' => $address->line_2,
				'zip' => $address->zip,
				'city' => $address->city,
				'user_id' => $user_id,
				'country_id' => $address->country_id,
				'state_id' => $address->state_id,
				'state_type_id' => $pending,
				'program_id' => $program_id,
				'notes' => $notes,
				'modified_by' => $user_id,
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
	public function read_order_details() {
		return self::where('physical_orders.id', $this->id)
		->join( TBL_ORDER_LINE_ITEMS . " AS li", 'physical_orders.id', '=', "li.physical_order_id")
		->join( TBL_MEDIUM_INFO . " AS mi", 'mi.id', '=', "li.medium_info_id")
		->join( TBL_MERCHANTS . " AS m", 'm.id', '=', "mi.merchant_id")
		->join( TBL_STATE_TYPES . " AS st", 'physical_orders.state_type_id', '=', "st.id")
		->join( TBL_USERS . " AS u", 'u.id', '=', "physical_orders.user_id")
		->select(
			[
				'physical_orders.id AS order_id',
				'physical_orders.program_id',
				'physical_orders.created_at',
				'm.name AS merchant_name',
				'm.merchant_code AS merchant_code',
				'mi.sku_value',
				'mi.code AS gift_code',
				'mi.pin AS gift_code_pin',
				'u.email'
			]
		)
		->first();
	}
}
