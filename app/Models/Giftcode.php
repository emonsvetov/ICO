<?php

namespace App\Models;

use App\Services\GiftcodeService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

use App\Models\Traits\IdExtractor;
use App\Models\Traits\Redeemable;
use Carbon\Carbon;

/**
 * @property date $purchase_date
 * @property date $redemption_date
 * @property date $redemption_datetime
 * @property int $redeemed_user_id
 */
class Giftcode extends Model
{
    use HasFactory, IdExtractor, Redeemable, SoftDeletes;

    protected $guarded = [];
    protected $table = 'medium_info';
    private static bool $all = false;


    const SYNC_STATUS_NOT_REQUIRED = 0;
    const SYNC_STATUS_REQUIRED = 1;
    const SYNC_STATUS_IN_PROGRESS = 2;
    const SYNC_STATUS_ERROR = 3;
    const SYNC_STATUS_SUCCESS = 5;


    public function newQuery()
    {
        $query = parent::newQuery();

        /*
        if (self::$all === false){
            $query->where('purchased_by_v2', '=', 0);
        }
        */

        return $query;
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

    public function read_list_redeemable_denominations_by_merchant($merchant_id = 0, $end_date = '', $virtual=false, $test=false) {
		// check to see if the merchant gets its gift codes from the its root merchant
		// if it does, query using the root merchant id instead
		$merchant = Merchant::where ( 'id', $merchant_id )->first();
		if ($merchant->get_gift_codes_from_root) {
		    echo 'merchant_id: ' . $merchant_id;
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
		$sql .= " AND
                        `purchased_by_v2` = 0";

		if(env('APP_ENV') != 'production' || $test){
		    $sql .= " AND medium_info_is_test = 1";
        }else{
		    $sql .= " AND medium_info_is_test = 0";
        }

		if($virtual){
		    $sql .= " AND virtual_inventory = 1";
        }else{
		    $sql .= " AND virtual_inventory = 0";
        }

		$sql .= " group by
                        sku_value, redemption_value, merchant_id";
		// add order by
		$sql .= "
                    ORDER BY
                    `sku_value`, `redemption_value` ASC";
		// execute the SQL to get the results

        try {
            $results = DB::select( DB::raw($sql), $params);
        } catch (\Exception $e) {
            throw new \Exception ( 'Could not get codes. DB query failed with error:' . $e->getMessage(), 400 );
        }
		return $results;
	}

	public static function createGiftcode($user, $merchant, $giftcode)	{

        // sku_value could be "$10", let's fix that
        $giftcode['sku_value'] = preg_replace("/[^,.0-9]/", '', $giftcode['sku_value']);
        $giftcode['sku_value'] = (float) $giftcode['sku_value'];

		if(!$merchant || !$giftcode ) return;
		$response = [];

        $currentGiftCode = Giftcode::getByCodeAndSkuValue($giftcode['code'], $giftcode['sku_value'], false);//Adding sku value in condition. Some codes have same code value with different sku value.
        if ($currentGiftCode){
            $response['success'] = true;
            $response['gift_code_id'] = $currentGiftCode->id;
            return $response;
        }

		if( $user && !is_object( $user ) && is_numeric( $user ))	{
			$user = User::find($user);
		}
		if( !is_object( $merchant ) && is_numeric( $merchant ))	{
			$merchant = Merchant::find($merchant);
		}
		if( !empty($giftcode['purchase_date']))	{
            $incomingFormat = 'm/d/Y'; //in csv imports

            if (strpos($giftcode['purchase_date'], '-')) { //from v2
                $incomingFormat = 'Y-m-d';
            }
			$giftcode['purchase_date'] = Carbon::createFromFormat($incomingFormat, $giftcode['purchase_date'])->format('Y-m-d');
		}

		//While importing it is setting "hold_until" to today. In the get query the today does not match so, a fix.
		$giftcode['hold_until'] = Carbon::now()->subDays(1)->format('Y-m-d');

		// if(env('APP_ENV') != 'production'){
		//     $giftcode['medium_info_is_test'] = 1;
        // }

		try{
		    $gift_code_id = self::insertGetId(
                $giftcode + ['merchant_id' => $merchant->id,'factor_valuation' => config('global.factor_valuation')]
            );
            $response['inserted'] = true;
        }catch(\Exception $e){
		    throw new \Exception ( 'Could not create codes. DB query failed with error:' . $e->getMessage(), 400 );
        }

		$response['gift_code_id'] = $gift_code_id;
		$user_account_holder_id = ($user && $user->account_holder_id)?$user->account_holder_id : 0;
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

	public static function getRedeemableListByMerchant($merchant, $filters = [], $orders=[]) {
		return ( new \App\Services\GiftcodeService )->getRedeemableListByMerchant( $merchant, $filters, $orders );
	}

	public static function getRedeemableListByMerchantAndRedemptionValue($merchant, $redemption_value = 0, $end_date = '', $args = []) { // $end_date = '2022-10-01' - what is that?
		// pr($end_date );die;
		$filters = [];
		if( (float) $redemption_value > 0 )	{
			$filters['redemption_value'] = (float) $redemption_value;
		}
		if( isValidDate($end_date) )	{
			$filters['end_date'] = $end_date;
		}
        if (isset($args['medium_info_is_test'])){
            $filters['medium_info_is_test'] = $args['medium_info_is_test'];
        }
        if (isset($args['purchased_by_v2'])){
            $filters['purchased_by_v2'] = $args['purchased_by_v2'];
        }

		return ( new \App\Services\GiftcodeService )->getRedeemableListByMerchant ( $merchant, $filters );
	}

	public static function holdGiftcode( $params = [] ) {
		if( empty($params['merchant_id']) || empty($params['merchant_account_holder_id']) || empty($params['sku_value']) || empty($params['redemption_value']) )
		{
			return ['errors' => ['Invalid data passed']];
		}

		if( empty($currency_id))	{
			$params['currency_id'] = Currency::getIdByType(config('global.default_currency'), true);
		}

		return self::_hold_giftcode($params);
	}

	public static function redeemPointsForGiftcodesNoTransaction( array $data)	{
		return self::_redeem_points_for_giftcodes_no_transaction( $data );
	}

	public static function redeemMoniesForGiftcodesNoTransaction( array $data)	{
		return self::_redeem_monies_for_giftcodes_no_transaction($data);
	}

	public static function transferGiftcodesToMerchantNoTransaction( array $data)	{
		return self::_transfer_giftcodes_to_merchant_no_transaction($data);
	}

	public static function handlePremiumDiff( array $params )	{
		if( empty($params['code']) || empty($params['journal_event_id']) )	{
			return ['errors' => sprintf('Invalid data passed to Giftcode::handlePremiumDiff')];
		}
		self::_handle_premium_diff( $params );
	}

	private static function _get_next_available_giftcode($program, $merchant_account_holder_id, $sku_value, $redemption_value
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
            'm.v2_merchant_id'
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
        ->whereNull('medium_info.redemption_date')
        ->where('medium_info.purchased_by_v2', '=', 0)
		->orderBy('medium_info.virtual_inventory', 'ASC')
		->limit(1);

		if(GiftcodeService::isTestMode($program)){
		    $query->where('medium_info_is_test', '=', 1);
        }else{
		    $query->where('medium_info_is_test', '=', 0);
        }

		return $query->first();
	}

	private static function _hold_giftcode( $params ) {

		extract($params);

		$giftcode = self::_get_next_available_giftcode(
		    $program,
			$merchant_account_holder_id,
			$sku_value,
			$redemption_value
		);

		if( !$giftcode )	{
			return ['errors' => sprintf('No available GiftCodes for merchant#:%d, sku_value:%s, redemption_value:%s', $merchant_id, $sku_value, $redemption_value) /*. print_r($params,true)*/ ];

		}

        /**
         * No available GiftCodes for merchant#:1, sku_value:5.0000, redemption_value:5.0000Array (
         * [user_account_holder_id] => 13137
         * [merchant_account_holder_id] => 4452
         * [redemption_value] => 5.0000 [sku_value] => 5.0000
         * [merchants] => Array ( [0] => Array ( [id] => 1 [account_holder_id] => 4452 [parent_id] => [name] => 1-800-Flowers [logo] => merchants/1/9AZ0Gb91UUfbCJHWGJZJkCwdhAVWyNrXLc4kbmux.png [icon] => merchants/1/xXaSX63PcqUWqx77oUi8YrbDoSeQL3mSXlHpkS2m.png [large_icon] => merchants/1/zBD6NxFqaInIa8Bdjj8Vic8zomX78T2Odu1nU7iN.png [banner] => [description] => <p>A national leader offering spectacular floral arrangements and plants through to gourmet food and wine baskets, as well as selections from Godiva, Cheryl &amp; Company, Plough and Hearth, The Popcorn Factory and more, ensures that you will have a wonderful shopping experience.</p> [website] => http://www.1800flowers.com [redemption_instruction] => <ol>

 </ol> [redemption_callback_id] => 0 [category] => [merchant_code] => FLO [website_is_redemption_url] => 0 [get_gift_codes_from_root] => 0 [is_default] => 1 [giftcodes_require_pin] => 1 [display_rank_by_priority] => 10 [display_rank_by_redemptions] => 10 [requires_shipping] => 0 [physical_order] => 0 [is_premium] => 0 [use_tango_api] => 1 [use_virtual_inventory] => 1 [virtual_denominations] => U935268:10:10,U683701:25:25,U106098:50:50 [virtual_discount] => 9.6000 [toa_id] => 38 [status] => 1 [display_popup] => 0 [created_at] => 2023-05-22T22:18:20.000000Z [updated_at] => 2023-12-27T08:06:42.000000Z [deleted_at] => [v2_merchant_id] => 203646 [v2_account_holder_id] => 203646 ) ) [merchant_id] => 1 [currency_id] => 1 )
         *
         */


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

    public static function readGiftcodeByMerchantAndId($merchant_account_holder_id = 0, $medium_info_id = 0)
    {
        return self::_read_by_merchant_and_medium_info_id($merchant_account_holder_id, $medium_info_id);
    }

	private static function _read_by_merchant_and_medium_info_id($merchant_account_holder_id = 0, $medium_info_id = 0) {
		$query = Posting::select([
			'medium_info.*',
			'merchants.v2_merchant_id',
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
		->orderBy('medium_info.virtual_inventory', 'ASC')
		->groupBy('medium_info.id');
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

    /**
     * @param string $code
     * @param bool $exception
     * @return Giftcode|null
     * @throws \Exception
     */
    public static function getByCode(string $code, bool $exception = true)
    {
        self::$all = true;
        $code = self::where('code', $code)->first();
        if (!$code && $exception){
            throw new \Exception('Gift Code not found.');
        }
        return $code;
    }

    public static function getByCodeAndSkuValue(string $code, float $skuValue, bool $exception = true)
    {
        self::$all = true;
        $code = self::where('code', $code)->where('sku_value', $skuValue)->first();
        if (!$code && $exception){
            throw new \Exception('Gift Code not found.');
        }
        return $code;
    }

    public static function getAllByProgramsQuery(array $programs)
    {
        return self::whereIn('redeemed_program_id', $programs);
    }

    public static function getAllByPrograms(array $programs)
    {
        return self::getAllByProgramsQuery($programs)->get();
    }

    public static function getCountByPrograms(array $programs)
    {
        return self::getAllByProgramsQuery($programs)->count();
    }

    public static function readNotSubmittedTangoCodes()
    {
        $isProduction = app()->environment('production') ? true : false;
        $query =  self::with('merchant')
            ->where('medium_info.virtual_inventory', '=', 1)
            ->whereNull('medium_info.tango_reference_order_id')
            ->whereNotNull('medium_info.redemption_date')
            ->where('medium_info.redemption_date' , ">=", "2023-08-01")
            ->where('medium_info.medium_info_is_test' , "=", $isProduction ? 0 : 1)
            ->orderBy('medium_info.sku_value', 'ASC');

        return $query->get();
    }

    public static function readNotSyncedCodes()
    {
        $isProduction = app()->environment('production') ? true : false;
        return self::with('merchant')
            ->where('medium_info.virtual_inventory', '=', 0)
            ->where('medium_info.medium_info_is_test', '=', $isProduction?0:1)
            ->whereNotNull('medium_info.redemption_date')
            ->whereIn('medium_info.v2_sync_status', [self::SYNC_STATUS_REQUIRED, self::SYNC_STATUS_ERROR])
            ->orderBy('medium_info.redemption_date', 'ASC')
            ->get();
    }
}
