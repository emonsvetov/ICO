<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\BillTo;

class BillToController extends Controller
{
    public function index($organization, $program)
    {
        $all = BillTo::where('organization_id', $organization)
                     ->where('program_id', $program)
                     ->orderBy('updated_at', 'desc')
                     ->get();

        return response($all);
    }

    public function lastUsed($organization, $program)
    {
        $lastUsed = BillTo::where('organization_id', $organization)
                          ->where('program_id', $program)
                          ->orderBy('updated_at', 'desc')
                          ->first();
        
        return response($lastUsed);
    }
}
