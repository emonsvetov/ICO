<?php
namespace App\Services;

use App\Models\MediumInfo;
use App\Models\Program;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Http\Resources\GiftcodeCollection;
use App\Models\Traits\IdExtractor;
use App\Models\Giftcode;
use App\Models\Merchant;
use function PHPUnit\Framework\isNull;

class GiftcodeService
{
    use IdExtractor;

    const GET_INVENTORY_ARGS = [
        'end_date'=>null,
        'medium_info_is_test'=> true,
        'count'=>false,
        'paginate'=>false
    ];

    const GET_REDEEMED_ARGS = self::GET_INVENTORY_ARGS + [
        'start_date'=>null,
        'redemption_value'=>null,
    ];

    public static function isTestMode( Program $program ){
        return ($program->is_demo || env('APP_ENV') != 'production' ) ? 1 : 0;
    }

    public function getRedeemable(Merchant $merchant, $is_demo = true)   {
        $merchant_id = self::extractId($merchant);
        if( !$merchant_id ) return;
        $where = [
            'merchant_id' => $merchant_id,
            'redemption_date' => 'null',
            'purchased_by_v2' => 0
        ];
        if ($is_demo || env('APP_ENV') != 'production' ){
            $where['medium_info_is_test'] = 1;
        }
        $orders = [
            'column' => 'medium_info.virtual_inventory',
            'direction' => 'ASC'
        ];
        $giftcodes = $this->getRedeemableListByMerchant($merchant, $where, $orders);

        return new GiftcodeCollection( $giftcodes );
    }

    public function createGiftcode($merchant, $giftcode, $user = null)
    {
        $response = [];
        $removable = ['supplier_code', 'someurl']; //handling 'unwanted' keys
        foreach($removable as $key) {
            if( isset($giftcode[$key]) ) unset( $giftcode[$key] );
        }

        if (isNull($user)){
            $user = auth()->user();
        }

        $result = Giftcode::createGiftcode(
            $user,
            $merchant,
            $giftcode
        );
        if( empty($result['success']))   {
            $response['errors'] = "Could not create giftcode";
        }   else {
            $response['result'] = $result;
        }
        return $response;
    }

    public function purchaseFromV2(Giftcode $giftcode, User $user, $v2_medium_info_id, $redeemed_merchant_id)
    {
        $error = '';
        try {
            $giftcode->purchased_by_v2 = true;
            $giftcode->v2_medium_info_id = $v2_medium_info_id;
            $giftcode->redeemed_merchant_id = $redeemed_merchant_id;
            $giftcode->redemption_date = Carbon::now()->format('Y-m-d');
            $giftcode->redemption_datetime = Carbon::now()->format('Y-m-d H:i:s');
            //$giftcode->redeemed_user_id = $user->id; not working
            $giftcode->save();
        } catch (\Exception $exception){
            $error = $exception->getMessage();
            Log::debug($exception->getMessage());
        }

        return [
            'success' => !$error,
            'error'   => $error
        ];
    }

	public function getRedeemableListByMerchantAndSkuValue($merchant = 0, $sku_value = 0, $end_date = '') { // $end_date = '2022-10-01' - what is that?
		$filters = [];
		if( (float) $sku_value > 0 )	{
			$filters['sku_value'] = (float) $sku_value;
		}
		if( isValidDate($end_date) )	{
			$filters['end_date'] = $end_date;
		}
		return $this->getRedeemableListByMerchant ( $merchant, $filters );
	}

	public function getRedeemableListByMerchant( int|Merchant $merchant, $filters = [], $orders=[] )
    {
        if( !($merchant instanceof Merchant) && is_numeric($merchant) )	{
            $merchant = Merchant::find($merchant);
        }

        if( !$merchant->exists() ) throw new \Exception ('Merchant not found');

        $topMerchant = null;
        if ($merchant->get_gift_codes_from_root) {
            $topMerchant = $merchant->getRoot();
        }

        $merchant_id = $merchant->get_gift_codes_from_root ? $topMerchant->id : $merchant->id;

		DB::statement("SET SQL_MODE=''"); //SQLSTATE[42000] fix!
		$query = Giftcode::selectRaw(
			"'{$merchant->id}' as merchant_id,
			'{$merchant->account_holder_id}' as merchant_account_holder_id,
			`redemption_value`,
			`sku_value`,
			`virtual_inventory`,
			`redemption_value` - `sku_value` as `redemption_fee`,
             COUNT(DISTINCT medium_info.`id`) as count"
		)
		->join('postings', 'postings.medium_info_id', '=', 'medium_info.id')
		->join('accounts AS a', 'postings.account_id', '=', 'a.id')
		->groupBy('sku_value')
		->groupBy('redemption_value')
		->orderBy('sku_value')
		->orderBy('redemption_value')
		->where('medium_info.merchant_id', $merchant_id)
		->where(function($query){
            $query->orWhere('medium_info.hold_until', '<=', DB::raw('NOW()'));
            $query->orWhereNull('medium_info.hold_until');
        });

        if(isset($filters['medium_info_is_test']) && $filters['medium_info_is_test'] )	{
            $query->where('medium_info_is_test', '=', 1);
        }else{
            $query->where('medium_info_is_test', '=', 0);
        }

		if( !empty($filters['redemption_value']) )	{
			$query = $query->where('redemption_value', '=', $filters['redemption_value']);
		}

		if( !empty($filters['sku_value']) )	{
			$query = $query->where('sku_value', '=', $filters['sku_value']);
		}

		if( isset($filters['redemption_date']) && $filters['redemption_date'] == 'null' )	{
			$query = $query->whereNull('redemption_date');
		}

		if( isset($filters['purchased_by_v2']))	{
			$query = $query->where('purchased_by_v2', '=', $filters['purchased_by_v2']);
		}

		if( !empty($filters['end_date']) && isValidDate($filters['end_date']) )	{
			$query = $query->where('purchase_date', '<=', $filters['end_date']);
			$query = $query->where(function($query1) use($filters) {
                $query1->orWhere('redemption_date', null)
                ->orWhere('redemption_date', '>', $filters['end_date']);
            });
		}

		if($orders && $orders['column'] && $orders['direction']){
            $query->orderBy($orders['column'], $orders['direction']);
        }

		/*
		$sql = $query->toSql();
        $bindings = $query->getBindings();

        $interpolatedSql = DB::raw(vsprintf($sql, $bindings));
        //throw new \Exception (print_r($bindings,true));
		*/

		return $query->get();
	}
    public function getInventoryCountByMerchant( int|Merchant $merchant, array $extra_args ) {
        return $this->getInventoryByMerchant( $merchant, $extra_args + ['count'=>true] );
    }
    /**
     * Reads Inventory of codes for a merchants
     *
     * Reads the list of gift codes that are directly in this merchant's inventory
     * Note: If the merchant gets its gift codes from it's parent merchant, its inventory will be 0.
     * Use "getRedeemableListByMerchant" if you want the codes that are available for redemption
     *
     * @param - $merchant, integer or instance of Merchant
     * @param - $end_date, date string|nullable
     * @return - collection of codes
     *
     */
	public function getInventoryByMerchant( int|Merchant $merchant, array $extra_args) {
        $extra_args = array_merge(self::GET_INVENTORY_ARGS, $extra_args);
        extract($extra_args); //NOTICE IT!!
        if( env('APP_ENV') == 'production' )    {
            $medium_info_is_test = 0;
        }
        if( !($merchant instanceof Merchant) && is_numeric($merchant) )	{
			$merchant = Merchant::find($merchant);
		}
        if( !$merchant->exists() ) throw new \Exception ('Merchant not found');

		if ( $end_date ) {
			//validate end date
		}
        DB::statement("SET SQL_MODE=''"); //SQLSTATE[42000] fix!

        $query = Giftcode::selectRaw(
            "`id`,
            `purchase_date`,
            `redemption_date`,
            `expiration_date`,
            `hold_until`,
            `redemption_value`,
            `cost_basis`,
            `discount`,
            `sku_value`,
            `pin`,
            `redemption_url`,
            `medium_info_is_test`,
            upper(substring(MD5(RAND()), 1, 20)) as `code`"
        );

        $query->withTrashed(); //the delete_at glitch in 't' binding below

        $query->fromSub(function ($query) use ($merchant, $end_date, $medium_info_is_test) {
            $query->select('medium_info.*');
            $query->from('medium_info')
            ->join('postings', 'medium_info.id', '=', 'postings.medium_info_id')
            ->join('accounts AS a', 'postings.account_id', '=', 'a.id');
            if ($medium_info_is_test) {
                $query->where('medium_info_is_test', 1);
            } else {
                $query->where('medium_info_is_test', 0);
            }
            if ($end_date) {
                $query->where(function($query) use ($end_date) {
                    $query->where('purchase_date', '<=', $end_date);
                    $query->where( function($query) use ($end_date) {
                        $query->orWhereNull('redemption_date');
                        $query->orWhere('redemption_date', '>', $end_date);
                    });
                });
            }   else {
                $query->whereNull('redemption_date');
            }
            $query->where('a.account_holder_id', $merchant->account_holder_id);
            $query->groupBy('medium_info.id');
        }, 't');

        if( $count )   {
            return $query->count();
        }

        if( $paginate )   {
            return $query->paginate( request()->get('limit', 10) );
        }

        return $query->get();
	}
    /**
     * Reads Redeemed codes for a merchants
     *
     * Reads the list of gift codes that are directly in this merchant's inventory
     * Note: If the merchant gets its gift codes from it's parent merchant, its inventory will be 0.
     * Use "getRedeemableListByMerchant" if you want the codes that are available for redemption
     *
     * @param - $merchant, integer or instance of Merchant
     * @param - $extra_args, different parameters including start_date, end_date etc. Check GET_REDEEMED_ARGS
     * @return - collection of codes
     *
     */
    public function getRedeemedByMerchant( int|Merchant $merchant, array $extra_args) {
        $extra_args =  array_merge(self::GET_REDEEMED_ARGS, $extra_args);

        extract($extra_args); //DO NOTICE IT!!

        if( !($merchant instanceof Merchant) && is_numeric($merchant) )	{
			$merchant = Merchant::find($merchant);
		}
        if( !$merchant->exists() ) throw new \Exception ('Merchant not found');

        if ( $start_date || $end_date ) {
			//validate date
		}

        DB::statement("SET SQL_MODE=''"); //SQLSTATE[42000] fix!

        $query = Giftcode::selectRaw(
            "medium_info.`id`,
            medium_info.`purchase_date`,
            medium_info.`redemption_date`,
            medium_info.`expiration_date`,
            medium_info.`hold_until`,
            medium_info.`redemption_value`,
            medium_info.`cost_basis`,
            medium_info.`discount`,
            medium_info.`sku_value`,
            medium_info.`pin`,
            medium_info.`redemption_url`,
            medium_info.`medium_info_is_test`,
            medium_info.`encryption`,
            upper(substring(MD5(RAND()), 1, 20)) as `code`,
            `users`.`account_holder_id`,
            CONCAT(users.first_name, ' ', users.last_name) as redeemed_by,
            `programs`.name as program_name,
            `users`.email"
        );

        $query->withTrashed(); //the delete_at glitch in 't' binding below

        $query->join('users', 'medium_info.redeemed_user_id', '=', 'users.id');
        $query->join('programs', 'medium_info.redeemed_program_id', '=', 'programs.id');
        $query->where('medium_info.redeemed_merchant_id', $merchant->id);

        if (is_float ( $redemption_value ) && $redemption_value != 0) {
            $query->where('medium_info.redemption_value', '=', $redemption_value);
        }
        if ($start_date) {
            $query->where('medium_info.redemption_date', '>=', $start_date);
        }

        if ($end_date) {
            $query->where('medium_info.redemption_date', '<=', $end_date);
        }

        $query->orderBy('purchase_date', 'DESC');

        if( $count )   {
            return $query->count();
        }

        if( $paginate )   {
            return $query->paginate( request()->get('limit', 10) );
        }

        return $query->get();
    }
    public function getRedeemedCountByMerchant( int|Merchant $merchant, array $extra_args ) {
        return $this->getRedeemedByMerchant( $merchant, $extra_args + [ 'count'=>1 ] );
    }

    public function getMediumInfoForRedemption()
    {
        if (strpos(env('RABBITMQ_QUEUE_EXCHANGE'),'qa_') !== false) {
            $mediumInfoIsTest = 1;
        }else{
            $mediumInfoIsTest = 0;
        }

        $mediumInfos = DB::table('medium_info')
            ->whereNull('redemption_date')
            ->where('virtual_inventory', 0)
            ->leftJoin('merchants', 'medium_info.merchant_id', '=', 'merchants.id')
            ->get([
                'medium_info.id',
                'medium_info.purchase_date',
                'medium_info.redemption_value',
                'medium_info.cost_basis',
                'medium_info.discount',
                'medium_info.sku_value',
                'medium_info.code',
                'medium_info.pin',
                'medium_info.redemption_url',
                'merchants.merchant_code'
            ]);

        return $mediumInfos;
    }

    public function addCodes($codes)
    {
        $user = User::where('id', 1)->first();
        foreach ($codes as $val) {
            $merchant = Merchant::where('v2_account_holder_id', $val['v2_account_holder_id'])->first();
            unset($val['v2_account_holder_id']);
            $res[] = $this->createGiftcode($merchant, $val, $user);
        }
        return $res;
    }
}
