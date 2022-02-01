<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\GiftCodeImportRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Merchant;
use App\Models\GiftCode;

class GiftCodeController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index( Merchant $merchant )
    {
        
        $giftCodes = GiftCode::paginate(request()->get('limit', 10));

        if ( $giftCodes->isNotEmpty() ) 
        { 
            return response( $giftCodes );
        }

        return response( [] );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function import(GiftCodeImportRequest $request, Merchant $merchant)
    {
        if ( !$merchant )
        {
            return response(['errors' => 'Invalid Merchant'], 422);
        }

        if($request->hasFile('csv_import')) {
            $csv_import = $request->file( 'csv_import' );
            if( $csv_import->isValid() )  {
                $filepath = $csv_import->store('import/giftcodes');
                if( $filepath ) {
                    $fileFullpath = storage_path() . '/app/public/' . $filepath;
                    $csvToArray = csvToArray($fileFullpath);
                    return response($csvToArray);
                }
                return response( $filepath );
            }
        }

        return $request->all();
        // $newMerchant = Merchant::create( $request->validated() );
        // return response([ 'merchant' => $newMerchant ]);
    }
}
