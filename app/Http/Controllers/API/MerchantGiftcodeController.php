<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\MerchantGiftcodeRequest;
use App\Http\Controllers\Controller;
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
        foreach( $csvData as $row ) {
            unset( $row['supplier_code'] );
            $imported[] = Giftcode::create(
                $row + ['merchant_id' => $merchant->id,'factor_valuation' => config('global.factor_valuation')]
            );
        }
        return response( $imported );
    }
}
