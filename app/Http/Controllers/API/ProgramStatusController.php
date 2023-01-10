<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\ProgramStatusRequest;
use App\Http\Controllers\Controller;
use App\Services\ProgramService;
use App\Models\Organization;
use App\Models\Program;

class ProgramStatusController extends Controller
{
    private ProgramService $programService;

    public function __construct(ProgramService $programService)
    {
        $this->programService = $programService;
    }

    public function index( Organization $organization )
    {
        return response( $this->programService->listStatus() );
    }

    public function update(ProgramStatusRequest $request, Organization $organization, Program $program )
    {
        $updated = $this->programService->updateStatus($request->validated(), $program);
        return response(['updated' => $updated]);
    }
}
