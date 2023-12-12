<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\ProgramReports; 
use App\Models\ProgramList; 

class ReportsController extends Controller
{
    public function getAllReports()
    {
        $reports = ProgramList::all(); 
        return response()->json($reports);
    }

    public function getReportsByProgramId($programId)
    {
        $reports = ProgramReports::where('program_id', $programId)->get();

        return response()->json($reports);
    }

}
