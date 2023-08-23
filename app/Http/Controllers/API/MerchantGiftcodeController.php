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
use App\Models\Program;
Use Exception;

class MerchantGiftcodeController extends Controller
{
    use CsvParser;

    public function index( Merchant $merchant )
    {
        $keyword = request()->get('keyword');
        $sortby = request()->get('sortby',  'id');
        $direction = request()->get('direction', 'asc');
        $from = request()->get('from', null);
        $virtual = request()->get('virtual', null);

        $fromDate = '';
        if($from){
            $fromDate = substr($from,4,11);
            $fromDate = date('Y-m-d', strtotime($fromDate)) . ' 00:00:00';
        }

        $to = request()->get('to', null);
        $toDate = '';
        if($to){
            $toDate = substr($to, 4, 11);
            $toDate = date('Y-m-d', strtotime($toDate)) . ' 23:59:59';
        }

        $where = ['merchant_id' => $merchant->id];

        if(env('APP_ENV') != 'production'){
		    $where['medium_info_is_test'] = 1;
        }

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

        $type = request()->get('type', '');
        if($type == 'redeemed'){
            $query->where(function ($q) {
                $q->whereNotNull('redemption_datetime');
                $q->orWhere('purchased_by_v2', '=' , 1);
            });

            if($fromDate){
                $query->where('redemption_datetime', '>=', $fromDate);
            }
            if($toDate){
                $query->where('redemption_datetime', '<=', $toDate );
            }
        }elseif($type == 'available'){
            $query->where(function ($q) {
                $q->whereNull('redemption_datetime');
                $q->where('purchased_by_v2', '=' , 0);
                $q->where('virtual_inventory', '=' , 0);
            });
        }elseif($type == 'virtual'){
            $query->where(function ($q) {
                $q->whereNull('redemption_datetime');
                $q->where('purchased_by_v2', '=' , 0);
                $q->where('virtual_inventory', '=' , 1);
            });
        }

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

    public function store( MerchantGiftcodeRequest $request, GiftcodeService $giftcodeService, Organization $organization, Program $program, Merchant $merchant )
    {
        $fileContents = request()->file('file_medium_info')->get();
        $csvData = $this->CsvToArray($fileContents);
        $file = request()->file('file_medium_info');
        $fileName = $file ? $file->getClientOriginalName() : '';
        if (isset($csvData[0]['supplier_code']) && $fileName == 'SyncGifCodesFromV2.csv'){
            $merchant = Merchant::getByMerchantCode($csvData[0]['supplier_code']);
        }
        $imported = [];

        foreach( $csvData as $row ) {
            $imported[] = $giftcodeService->createGiftcode($merchant, $row );
        }
        return response( $imported );
    }

    public function storeVirtual( MerchantGiftcodeRequest $request, GiftcodeService $giftcodeService, Organization $organization, Program $program, Merchant $merchant )
    {
        $fileContents = request()->file('file_medium_info')->get();
        $csvData = $this->CsvToArray($fileContents);
        $file = request()->file('file_medium_info');
        $imported = [];

        foreach( $csvData as $row ) {
            $row['virtual_inventory'] = 1;
            $imported[] = $giftcodeService->createGiftcode($merchant, $row );
        }
        return response( $imported );
    }

}
