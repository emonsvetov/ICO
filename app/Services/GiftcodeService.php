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
// use DB;

class GiftcodeService 
{
    use IdExtractor;

    public function getRedeemable(Merchant $merchant)   {
        $merchant_id = self::extractId($merchant);
        if( !$merchant_id ) return;
        $where = [
            'merchant_id' => $merchant_id,
            'redemption_date' => null,
        ];
        $giftcodes = Giftcode::getRedeemableListByMerchant($merchant, $where );
        // pr(DB::getQueryLog());
        return new GiftcodeCollection( $giftcodes );
    }

    public function createGiftcode($merchant, $giftcode )    {
        try{
            $response = [];
            $removable = ['supplier_code', 'someurl']; //handling 'unwanted' keys
            $merchant_account_holder_id = $merchant->account_holder_id;
            $owner_account_holder_id = Owner::find(1)->id;
            foreach($removable as $key) {
                if( isset($giftcode[$key]) ) unset( $giftcode[$key] );
            }
            $journal_event_type_id = JournalEventType::getIdByType( "Purchase gift codes for monies" );
            $user_account_holder_id = auth()->id();
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
        }   catch (Exception $e)    {
            $response['errors'] = sprintf('Exception while creating giftcode. Error:%s in line %d ', $e->getMessage(), $e->getLine());
        }
        $response;
    }
}
