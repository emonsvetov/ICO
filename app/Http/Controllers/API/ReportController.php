<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Organization;

class ReportController extends Controller
{
    public function index(Request $request, Organization $organization )
    {
        // if( !$organization->exists )
        // {
        //     return response(['errors' => 'Invalid Organization'], 422);
        // }

        $merchant_id = explode(',', $request->get( 'merchant_id' ));
        $end_date = $request->get( 'end_date' );

        if( $end_date ) {
            $end_date  = date("Y-m-d H:i:s", strtotime($end_date)); 
        }

        return response( [$merchant_id, $end_date]);
    }
}
