<?php
namespace App\Services;

use App\Http\Resources\GiftcodeCollection;
use App\Models\Traits\IdExtractor;
use App\Models\JournalEventType;
use App\Models\JournalEvent;
use App\Models\FinanceType;
use App\Models\MediumType;
use App\Models\Currency;
use App\Models\Giftcode;
use App\Models\Merchant;
use App\Models\Account;
use App\Models\Owner;
use Carbon\Carbon;
use DB;
use Illuminate\Support\Facades\Log;

class GiftcodeService
{
    use IdExtractor;

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
        $giftcodes = Giftcode::getRedeemableListByMerchant($merchant, $where );
        return new GiftcodeCollection( $giftcodes );
    }

    public function createGiftcode( $merchant, $giftcode )    {
        $response = [];
        DB::beginTransaction();
        try{
            $removable = ['supplier_code', 'someurl']; //handling 'unwanted' keys
            foreach($removable as $key) {
                if( isset($giftcode[$key]) ) unset( $giftcode[$key] );
            }
            $result = Giftcode::createGiftcode(
                auth()->user(),
                $merchant,
                $giftcode
            );
            if( !empty($result['success']))   {
                DB::commit();
            }   else    {
                $response['errors'] = "Could not create giftcode";
                DB::rollback();
            }
            $response['result'] = $result;
        }   catch (Exception $e)    {
            DB::rollback();
            $response['errors'] = sprintf('Exception while creating giftcode. Error:%s in line %d ', $e->getMessage(), $e->getLine());
        }
        return $response;
    }

    /**
     * @param Giftcode $giftcode
     * @return bool
     */
    public function purchaseFromV2(Giftcode $giftcode): bool
    {
        $result = false;
        try {
            $giftcode->purchased_by_v2 = true;
            $giftcode->purchase_date = Carbon::now()->format('Y-m-d');
            $result = $giftcode->save();
        } catch (\Exception $exception){
            Log::debug($exception->getMessage());
        }

        return $result;
    }
}
