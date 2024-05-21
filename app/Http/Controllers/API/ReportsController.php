<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ProgramReports;
use App\Models\ProgramList;
use App\Services\ProgramService;

class ReportsController extends Controller
{
    public function getAllReports()
    {
        $reports = ProgramList::all();
        return response()->json($reports);
    }

    public function getReportsByProgramId($programId, ProgramService $service)
    {
        $rootPogramId = $service->getRootPogramId($programId);
        $reports = ProgramReports::where('program_id', $rootPogramId)
            ->with(['report' => function ($query) {
                $query->select('id', 'name', 'url');
            }])
            ->get();

        $reports = $reports->map(function ($report) {
            return [
                'id' => $report->id,
                'program_id' => $report->program_id,
                'report_id' => $report->report_id,
                'name' => $report->report->name,
                'link' => $report->report->url,
            ];
        });

        return response()->json($reports);
    }

    public function getSelectedReportsByProgramId($programId)
    {
        $selectedReports = ProgramReports::where('program_id', $programId)
                        ->pluck('report_id');

        return response()->json($selectedReports);
    }

}

