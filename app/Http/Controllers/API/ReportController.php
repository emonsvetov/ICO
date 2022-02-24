<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Organization;
use App\Models\ProgramBudget;

class ReportController extends Controller
{
    public function index(Request $request, Organization $organization, $type )
    {
        // if( !$organization->exists )
        // {
        //     return response(['errors' => 'Invalid Organization'], 422);
        // }
        if ( !$organization || !$type )
        {
            return response(['errors' => 'Invalid Organization or Request'], 422);
        }
        switch ($type) {
            case 0:
                $merchant_id = explode(',', $request->get( 'merchant_id' ));
                $end_date = $request->get( 'end_date' );

                if( $end_date ) {
                    $end_date  = date("Y-m-d H:i:s", strtotime($end_date));
                }

                return response( [$merchant_id, $end_date]);

                break;
            case 3:
                $program_ids = explode(',', $request->get( 'program_id' ));
                $year = $request->get( 'year' );

                $query = ProgramBudget::select('budget','program_id')->whereIn('program_id', $program_ids)
                                    ->where('year', '=', $year)
                                    ->get()->groupBy('program_id');

                return response($query);
                break;

        }


    }
}
