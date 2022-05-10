<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\MerchantGiftcodeRequest;
use App\Http\Controllers\Controller;
use App\Services\GiftcodeService;
use App\Http\Traits\CsvParser;
use Illuminate\Http\Request;
use App\Models\Organization;
use App\Rules\CsvValidator;
use App\Models\Merchant;
use App\Models\Giftcode;
Use Exception;

class MerchantGiftcodeController extends Controller
{
    use CsvParser;

    public function __construct(GiftcodeService $giftcodeService)
    {
        $this->giftcodeService = $giftcodeService;
    }

    public function index( Merchant $merchant )
    {
        $keyword = request()->get('keyword');
        $sortby = request()->get('sortby', 'id');
        $direction = request()->get('direction', 'asc');

        $where = ['merchant_id' => $merchant->id];

        if( $sortby == "name" )
        {
            $collation =  "COLLATE utf8mb4_unicode_ci"; //COLLATION is required to support case insensitive ordering
            $orderByRaw = "{$sortby} {$collation} {$direction}";
        }
        else
        {
            $orderByRaw = "{$sortby} {$direction}";
        }

        $query = Giftcode::where( $where );

        if( $keyword )
        {
            $query = $query->where(function($query1) use($keyword) {
                $query1->orWhere('id', 'LIKE', "%{$keyword}%")
                ->orWhere('code', 'LIKE', "%{$keyword}%");
            });
        }

        $query = $query->orderByRaw($orderByRaw);

        if ( request()->has('minimal') )
        {
            $giftcodes = $query->select('id', 'code')
            ->get();
        } else {
            $giftcodes = $query->paginate(request()->get('limit', config('global.paginate_limit')));
        }

        if ( $giftcodes->isNotEmpty() )
        {
            return response( $giftcodes );
        }

        return response( [] );
    }

    public function store( MerchantGiftcodeRequest $request, Merchant $merchant )
    {
        $fileContents = request()->file('file_medium_info')->get();
        $csvData = $this->CsvToArray($fileContents);
        $imported = [];
        $removable = ['supplier_code', 'someurl']; //handling 'unwanted' keys
        $merchant_account_holder_id = $merchant->account_holder_id;
        foreach( $csvData as $row ) {
            foreach($removable as $key) {
                if( isset($row[$key]) ) unset( $row[$key] );
            }
            $fields = array (
				'purchase_date',
				'redemption_value',
				'cost_basis',
				'discount',
				'sku_value',
				'pin',
				'redemption_url',
				'code',
				'expiration_date',
				'medium_info_is_test'
		    );
            $info = array ();
            // iterate to every field for the gift code information
            foreach ( $fields as $key ) {
                if( isset($row[$key]) ) $info[$key] = $row[$key];
            }
            // intialize the gift_code handler
            $gift_code_details = array ();
            foreach ( $info as $index => $value ) {
                if ($index == 'expiration_date') {
                    continue;
                }
                $gift_code_details [] = $value;
            }
            $journal_event_type_id = JournalEventType::getIdByType( "Purchase gift codes for monies" );
            $user_account_holder_id = auth()->user('id');
            $journal_event_id = JournalEvent::insertGetId([
                'journal_event_type_id' => $journal_event_type_id,
                'prime_account_holder_id' => $user_account_holder_id,
                'created_at' => now()
            ]);
            $_asset = FinanceType::getIdByName('Asset', true);
            $_gift_codes = MediumType::getIdByName('Points', true);
            $_monies = MediumType::getIdByName('Monies', true);

            Account::postings(
                $merchant_account_holder_id,
                'Gift Codes Available',
                $_asset,
                $_gift_codes,
                $owner_account_holder_id,
                $escrow_credit_account,
                $liability,
                $points,
                $journal_event_id,
                $award_amount,
                1, //qty
                '', // medium_fields
                '', // medium_values
                null, // medium_info_id
                $currency_id
            );

            $imported[] = Giftcode::create(
                $row + ['merchant_id' => $merchant->id,'factor_valuation' => config('global.factor_valuation')]
            );
        }
        return response( $imported );
    }

    public function redeemable( Merchant $merchant )
    {
        return $this->giftcodeService->getRedeemable($merchant);
    }
}
