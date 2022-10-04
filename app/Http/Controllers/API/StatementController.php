<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProgramStatementRequest;
use App\Services\StatementService;
use App\Models\Organization;
use App\Models\Program;
use App\Models\Award;
Use Exception;

class StatementController extends Controller
{
    public function show(ProgramStatementRequest $request, Organization $organization, Program $program, StatementService $statementService )
    {
        $statement = $statementService->get($program, $request->validated());
        return response()->json($statement);
        // return $statement;
        // return response(['statement'=>$statement]);
    }
}
