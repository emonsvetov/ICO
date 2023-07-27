<?php
namespace App\Http\Controllers\API;

use App\Services\Report\ReportService;
use App\Http\Requests\ReportRequest;
use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Services\reports\ReportFactory;
use App\Services\reports\ReportServiceAbstract;
use Illuminate\Http\Request;
use App\Models\Organization;
use App\Models\ProgramBudget;


class ReportController extends Controller
{
    // public function index(ReportRequest $request, Organization $organization )
    // {
    //     $type = request('report_type');

    //     if( !$type )    {
    //         return response(['errors' => 'Invalid Report Request'], 422);
    //     }

    //     switch ( $type ) {
    //         case ReportService::TYPE_INVENTORY:
    //             $report = $this->inventoryService->getReport();
    //             return $report;
    //         break;
    //         case ReportService::TYPE_PROGRAM_BUDGET:
    //         case 3:
    //             $program_ids = explode(',', request( 'program_id' ));
    //             $year = request( 'year' );
    //             $result = ProgramBudget::join("months", "program_budget.month_id", "=", "months.id")
    //                 ->select("budget", "program_id", "months.name AS month")
    //                 ->whereIn('program_id', $program_ids)
    //                 ->where('is_notified', "=", 1)
    //                 ->get()
    //                 ->groupBy('program_id');
    //             return response($result);
    //         break;
    //     }
    //     // return $this->reportService->getReport();
    // }

    public function show(Organization $organization, Program $program, string $title, Request $request, ReportFactory $reportFactory)
    {
        try {
            /** @var ReportServiceAbstract $report */
            $report = $reportFactory->build($title, $request->all());
            $response = $report->getReport();
            return response($response);
        } catch (\Exception $e) {
            $msg = sprintf('%s in line %d of file %s', $e->getMessage(), $e->getLine(), $e->getFile());
            return response(['errors' => 'Error report generate', 'e' => $msg], 422);
        }
    }
}
