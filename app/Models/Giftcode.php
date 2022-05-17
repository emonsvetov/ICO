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

	public function create($user, $merchant, $giftcode)	{
		if( !$user || !$merchant || !$giftcode ) return;
		$response = [];
		if( !is_object( $user ) && is_numeric( $user ))	{
			$user = Merchant::find($user);
		}
		if( !is_object( $merchant ) && is_numeric( $merchant ))	{
			$merchant = Merchant::find($merchant);
		}
		$user_account_holder_id = $user->account_holder_id;
		$merchant_account_holder_id = $merchant->account_holder_id;
        $owner_account_holder_id = Owner::find(1)->id;
		$journal_event_type_id = JournalEventType::getIdByType( "Purchase gift codes for monies" );
		$journal_event_id = JournalEvent::insertGetId([
			'journal_event_type_id' => $journal_event_type_id,
			'prime_account_holder_id' => $user_account_holder_id,
			'created_at' => now()
		]);
		$_asset = FinanceType::getIdByName('Asset', true);
		$_gift_codes = MediumType::getIdByName('Gift Codes', true);
		$_monies = MediumType::getIdByName('Monies', true);
		$currency_id = Currency::getIdByType(config('global.default_currency'), true);

		$result = Account::postings(
			$merchant_account_holder_id,
			'Gift Codes Available',
			$_asset,
			$_gift_codes,
			$owner_account_holder_id,
			'Cash',
			$_asset,
			$_monies,
			$journal_event_id,
			$giftcode['sku_value'],
			1, //qty
			$giftcode + ['merchant_id' => $merchant->id,'factor_valuation' => config('global.factor_valuation')], //medium_info
			null, // medium_info_id
			$currency_id
		);

		if( isset($result['postings']) && sizeof($result['postings']) == 2 )  {
			$response['success'] = true;
			$response['postings'] = $result['postings'];
		}
		return $response;
	}

	public function getRedeemableListByMerchant($merchant, $filters = []) {
		return self::_read_redeemable_list_by_merchant( $merchant, $filters );
	}

	public function getRedeemableListByMerchantAndRedemptionValue($merchant, $redemption_value = 0, $end_date = '2022-10-01') {
		// pr($end_date );die;
		$filters = [];
		if( (float) $redemption_value > 0 )	{
			$filters['redemption_value'] = (float) $redemption_value;
		}
		if( isValidDate($end_date) )	{
			$filters['end_date'] = $end_date;
		}
		
		return self::_read_redeemable_list_by_merchant ( $merchant, $filters );
	}

	private function _read_redeemable_list_by_merchant( $merchant, $filters = [] )	{

		if( !is_object($merchant) && is_numeric($merchant) )	{
			$merchant = Merchant::find($merchant);
		}

		DB::statement("SET SQL_MODE=''"); //SQLSTATE[42000] fix!
		DB::enableQueryLog();
		$query = self::selectRaw(
			"'{$merchant->id}' as merchant_id,
			'{$merchant->account_holder_id}' as merchant_account_holder_id,
			a.`account_holder_id` as top_level_merchant_id,
			`redemption_value`,
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
		->where('medium_info.hold_until', '<=', now());

		if( !empty($filters['redemption_value']) )	{
			$query = $query->where('redemption_value', '=', $filters['redemption_value']);
		}

		if( !empty($filters['sku_value']) )	{
			$query = $query->where('sku_value', '=', $filters['sku_value']);
		}

		if( !empty($filters['end_date']) && isValidDate($filters['end_date']) )	{
			$query = $query->where('purchase_date', '<=', $filters['end_date']);
			$query = $query->where(function($query1) use($filters) {
                $query1->orWhere('redemption_date', null)
                ->orWhere('redemption_date', '>', $filters['end_date']);
            });
		}

		return $query->get();
	}

	public function holdGiftcode( $params = [] ) {
		if( empty($params['user_account_holder_id']) || empty($params['merchant_account_holder_id']) || empty($params['sku_value']) || empty($params['redemption_value']) )
		{
			return ['errors' => ['Invalid data passed']];
		}

		if( empty($currency_id))	{
			$params['currency_id'] = Currency::getIdByType(config('global.default_currency'), true);
		}

		return self::_hold_giftcode($params);
	}

	private function _hold_giftcode( $params ) {
		
		extract($params);

		return $params;

		$sql = "CALL sp_journal_hold_gift_code(
            {$merchant_id},
            {$sku_value},
            {$redemption_value},
            {$currency_id},
            @result,@code,@gift_code_id);";

        //$debug['sql'] = $sql;

		try {
			$result = DB::statement ( DB::raw($sql) );
		} catch (Exception $e) {
			throw new RuntimeException ( 'SQL statement to query CALL sp_journal_hold_gift_code', 500 );
		}

		$sql = "SELECT @result as result, @code as code, @gift_code_id as gift_code_id";
		try {
			$result = DB::select ( DB::raw($sql) );
			//$debug['$result'] = $result;
		} catch (Exception $e) {
			throw new RuntimeException ( 'SQL statement to query GiftCode:_hold_giftcode failed', 500 );
		}

		$row = $result && count($result) ? current($result) : null;

		//pr($row);

		//$debug['$row'] = $row;

		if (!$row OR strtolower ( $row->result ) != 'success') {
			throw new RuntimeException ( 'Internal query failed, please contact the API administrator', 500 );
		}

		$code = self::_read_by_merchant_and_medium_info_id ( $merchant_id, ( int ) $row->gift_code_id, $debug);

		$debug['$code_1'][] = $code;

		// query merchant information based on the returned merchant_account_holder_id
		// put into merchant object
		if( count($merchants) > 0 && isset($merchants[$merchant_id]))	{
			$merchant = $merchants[$merchant_id];
		}	else {
			$merchant = Merchant::read ( ( int ) $merchant_id );
		}

		$debug['$merchant'][] = $merchant;

		// add merchant information to the object
		foreach ( $merchant as $key => $value ) {
			if($key=='id') continue; //skip so it is not overwritten by merchant id
			$code->$key = $value;
		}
		// format the code object with merchant information
		$code = self::_format_result ( array (
				$code 
		) );
		$code = reset ( $code );
		$debug['$code_2'][] = $code;
		// return the gift code information
		return $code;
	}

	private static function _run_gift_code_callback($callback = ExternalCallbackObject, $program_id, $user_id, $merchant_id, $data = array()) {
		$params = array ();
		$user = $this->users_model->read_by_owner_id ( ( int ) $user_id, ( int ) $program_id );
		$program = $this->programs_model->get_program_info ( ( int ) $program_id );
		// Get the program's default contact user
		$default_contact = $this->users_model->read_by_owner_id ( ( int ) $program->default_contact_account_holder_id, ( int ) $program_id );
		// Add more information to the data to be passed to the callback
		$data ['program_id'] = $program_id;
		$data ['program_external_id'] = $program->external_id;
		$data ['user_id'] = $user->account_holder_id;
		$data ['user_external_id'] = $user->organization_uid;
		$data ['user_email'] = $user->email;
		$data ['user_first_name'] = $user->first_name;
		$data ['user_last_name'] = $user->last_name;
		$data ['from_first_name'] = $default_contact->first_name;
		$data ['from_last_name'] = $default_contact->last_name;
		$data ['from_email'] = $default_contact->email;
		$program_custom_fields = $this->users_model->read_custom_fields_by_owner ( ( int ) $program_id, ( int ) $user_id );
		if (count ( $program_custom_fields ) > 0) {
			foreach ( $program_custom_fields as $program_custom_field ) {
				$data [$program_custom_field->name] = $program_custom_field->value;
			}
		}
		$response = $this->external_callback->call ( $callback, $data, ( int ) $user_id, ( int ) $merchant_id );
		return $response;
	}
}
