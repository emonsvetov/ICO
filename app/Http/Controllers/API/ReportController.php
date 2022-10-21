<?php

namespace App\Http\Controllers\API;
use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Services\reports\ReportFactory;
use App\Services\reports\ReportServiceAbstract;
use Illuminate\Http\Request;
use App\Models\Organization;
use App\Models\ProgramBudget;
//use App\Http\Requests\ReportRequest;

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

                $result = ProgramBudget::join("months", "program_budget.month_id", "=", "months.id")
                    ->select("budget", "program_id", "months.name AS month")
                    ->whereIn('program_id', $program_ids)
                    ->where('is_notified', "=", 1)
                    ->get()
                    ->groupBy('program_id');

                return response($result);
                break;

        }

    }

    public function show(Organization $organization, Program $program, string $title, Request $request, ReportFactory $reportService)
    {
        try {
            /** @var ReportServiceAbstract $report */
            $report = $reportService->build($title, $request->all());
            $response = $report->getReport();
            return response($response);
        } catch (\Exception $e) {
            return response(['errors' => 'Error report generate', 'e' => $e->getMessage()], 422);
        }
    }
}
