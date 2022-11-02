<?php
namespace App\Http\Controllers\API;

use App\Services\Report\InventoryService;
use App\Services\Report\ReportService;
use App\Http\Requests\ReportRequest;
use App\Http\Controllers\Controller;
use App\Models\Organization;

class ReportController extends Controller
{
    public function __construct(InventoryService $inventoryService)
    {
        $this->inventoryService = $inventoryService;
    }
    public function index(ReportRequest $request, Organization $organization )
    {
        $type = request('report_type');

        if( !$type )    {
            return response(['errors' => 'Invalid Report Request'], 422);
        }

        switch ( $type ) {
            case ReportService::TYPE_INVENTORY:
                $report = $this->inventoryService->getReport();
                return $report;
            break;
            case ReportService::TYPE_PROGRAM_BUDGET:
            case 3:
                $program_ids = explode(',', request( 'program_id' ));
                $year = request( 'year' );
                $result = ProgramBudget::join("months", "program_budget.month_id", "=", "months.id")
                    ->select("budget", "program_id", "months.name AS month")
                    ->whereIn('program_id', $program_ids)
                    ->where('is_notified', "=", 1)
                    ->get()
                    ->groupBy('program_id');
                return response($result);
            break;
        }
        // return $this->reportService->getReport();
    }
}
