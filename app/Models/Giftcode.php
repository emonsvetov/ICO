<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\Models\Traits\IdExtractor;
use App\Models\Traits\Redeemable;
use Carbon\Carbon;
use DB;

class Giftcode extends Model
{
    use HasFactory, IdExtractor, Redeemable, SoftDeletes;

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

	public function createGiftcode($user, $merchant, $giftcode)	{

		if( !$user || !$merchant || !$giftcode ) return;
		$response = [];

		if( !is_object( $user ) && is_numeric( $user ))	{
			$user = User::find($user);
		}
		if( !is_object( $merchant ) && is_numeric( $merchant ))	{
			$merchant = Merchant::find($merchant);
		}
		if( !empty($giftcode['purchase_date']))	{
			$giftcode['purchase_date'] = Carbon::createFromFormat('d/m/Y', $giftcode['purchase_date'])->format('Y-m-d');
		}

		//While importing it is setting "hold_until" to today. In the get query the today does not match so, a fix.
		$giftcode['hold_until'] = Carbon::now()->subDays(1)->format('Y-m-d');

		$gift_code_id = self::insertGetId(
			$giftcode + ['merchant_id' => $merchant->id,'factor_valuation' => config('global.factor_valuation')]
		);
		$response['gift_code_id'] = $gift_code_id;
		$user_account_holder_id = $user->account_holder_id;
		$merchant_account_holder_id = $merchant->account_holder_id;
        $owner_account_holder_id = Owner::find(1)->account_holder_id;
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
			null, //medium_info
			$gift_code_id, // medium_info_id
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

	public function getRedeemableListByMerchantAndSkuValue($merchant = 0, $sku_value = 0, $end_date = '2022-10-01') {

		$filters = [];
		if( (float) $sku_value > 0 )	{
			$filters['sku_value'] = (float) $sku_value;
		}
		if( isValidDate($end_date) )	{
			$filters['end_date'] = $end_date;
		}
		
		return self::_read_redeemable_list_by_merchant ( $merchant, $filters );
	
	}

	public function holdGiftcode( $params = [] ) {
		if( empty($params['merchant_id']) || empty($params['merchant_account_holder_id']) || empty($params['sku_value']) || empty($params['redemption_value']) )
		{
			return ['errors' => ['Invalid data passed']];
		}

		if( empty($currency_id))	{
			$params['currency_id'] = Currency::getIdByType(config('global.default_currency'), true);
		}

		return self::_hold_giftcode($params);
	}

	public function redeemPointsForGiftcodesNoTransaction( array $data)	{
		return self::_redeem_points_for_giftcodes_no_transaction( $data );
	}

	public function redeemMoniesForGiftcodesNoTransaction( array $data)	{
		return self::_redeem_monies_for_giftcodes_no_transaction($data);
	}	
	
	public function transferGiftcodesToMerchantNoTransaction( array $data)	{
		return self::_transfer_giftcodes_to_merchant_no_transaction($data);
	}

	public function handlePremiumDiff( array $params )	{
		if( empty($params['code']) || empty($params['journal_event_id']) )	{
			return ['errors' => sprintf('Invalid data passed to Giftcode::handlePremiumDiff')];
		}
		self::_handle_premium_diff( $params );
	}

	private function _get_next_available_giftcode($merchant_account_holder_id, $sku_value, $redemption_value
	)	
	{
		$query = self::select([
			'medium_info.code',
			'medium_info.id',
			'medium_info.sku_value',
			'medium_info.hold_until',
			'medium_info.pin',
			'posts.account_id',
			'm.name',
		])
		->join('postings AS posts', 'posts.medium_info_id', '=', 'medium_info.id')
		->join('accounts AS a', 'posts.account_id', '=', 'a.id')
		->join('merchants AS m', 'm.account_holder_id', '=', 'a.account_holder_id')
		->join('medium_types AS mt', 'mt.id', '=', 'a.medium_type_id')
		->where('mt.name', 'Gift Codes')
		->where('m.account_holder_id', $merchant_account_holder_id)
		->where('medium_info.redemption_value', $redemption_value)
		->where('medium_info.sku_value', $sku_value)
		->where('medium_info.hold_until', '<=', now())
		->orderBy('medium_info.id')
		->limit(1)
		;
		return $query->first();
	}

	private function _hold_giftcode( $params ) {
		
		extract($params);

		$giftcode = self::_get_next_available_giftcode(
			$merchant_account_holder_id, 
			$sku_value, 
			$redemption_value
		);

		if( !$giftcode )	{
			return ['errors' => sprintf('No available GiftCodes for merchant#:%d, sku_value:%s, redemption_value:%s', $merchant_id, $sku_value, $redemption_value)];
		}

		//Reserve Giftcode

		$reserved = $giftcode->update([
			'hold_until' => date('Y-m-d H:i:s', strtotime('+5 minutes'))
		]);

		if( !$reserved )	{
			return ['errors' => sprintf('Could not reserve code for merchant#:%d, sku_value:%s, redemption_value:%s', $merchant_id, $sku_value, $redemption_value)];
		}

		$code = self::_read_by_merchant_and_medium_info_id ( $merchant_account_holder_id, $giftcode->id);

		if( !$code )	{
			return ['errors' => sprintf('Could not read code for merchant#:%d, sku_value:%s, redemption_value:%s', $merchant_d, $sku_value, $redemption_value)];
		}

		return $code;
	}

	private function _read_by_merchant_and_medium_info_id($merchant_account_holder_id = 0, $medium_info_id = 0) {
		$query = Posting::select([
			'medium_info.*',
			'postings.created_at'
		])
		->join('medium_info', 'medium_info.id', '=', 'postings.medium_info_id')
		->join('accounts', 'accounts.id', '=', 'postings.account_id')
		->join('merchants', 'merchants.account_holder_id', '=', 'accounts.account_holder_id')
		->join('medium_types', 'medium_types.id', '=', 'accounts.medium_type_id')
		->where('medium_info.id', $medium_info_id)
		->where('medium_types.id', 1)
		->where('merchants.account_holder_id', $merchant_account_holder_id)
		->orderBy('medium_info.purchase_date')
		->orderBy('medium_info.id')
		->groupBy('medium_info.id')
		;
		return $query->first();
	}

	private static function _run_gift_code_callback($callback = ExternalCallbackObject, $program_id, $user_id, $merchant_id, $data = array()) {
		$response['errors'] = "External Callback feature is not implemented in rebuild yet";
		// $params = array ();
		// $user = $this->users_model->read_by_owner_id ( ( int ) $user_id, ( int ) $program_id );
		// $program = $this->programs_model->get_program_info ( ( int ) $program_id );
		// // Get the program's default contact user
		// $default_contact = $this->users_model->read_by_owner_id ( ( int ) $program->default_contact_account_holder_id, ( int ) $program_id );
		// // Add more information to the data to be passed to the callback
		// $data ['program_id'] = $program_id;
		// $data ['program_external_id'] = $program->external_id;
		// $data ['user_id'] = $user->account_holder_id;
		// $data ['user_external_id'] = $user->organization_uid;
		// $data ['user_email'] = $user->email;
		// $data ['user_first_name'] = $user->first_name;
		// $data ['user_last_name'] = $user->last_name;
		// $data ['from_first_name'] = $default_contact->first_name;
		// $data ['from_last_name'] = $default_contact->last_name;
		// $data ['from_email'] = $default_contact->email;
		// $program_custom_fields = $this->users_model->read_custom_fields_by_owner ( ( int ) $program_id, ( int ) $user_id );
		// if (count ( $program_custom_fields ) > 0) {
		// 	foreach ( $program_custom_fields as $program_custom_field ) {
		// 		$data [$program_custom_field->name] = $program_custom_field->value;
		// 	}
		// }
		// $response = $this->external_callback->call ( $callback, $data, ( int ) $user_id, ( int ) $merchant_id );
		return $response;
	}
}
