<?php
namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;
use App\Models\Organization;

class ReportController extends Controller
{
    private $reportService;
    public function __construct(ReportService $service)
    {
        $this->reportService = $service;
    }
    public function index(Request $request, Organization $organization, $type = '' )
    {
        return $this->reportService->getReport( $type );
    }
}
