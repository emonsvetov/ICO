<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\Organization;
use App\Models\ProgramApproval;
use App\Services\ProgramApprovalService;
use App\Http\Requests\ProgramApprovalRequest;
use App\Http\Requests\ProgramApprovalAssignmentRequest;
use Illuminate\Http\Request;

class ProgramApprovalController extends Controller
{
    protected $programApprovalService;

    public function __construct(ProgramApprovalService $programApprovalService)
    {
        $this->programApprovalService = $programApprovalService;
    }

    public function store(ProgramApprovalRequest $programApprovalRequest, Organization $organization, Program $program)
    {
        $data = $programApprovalRequest->validated();
        $data = $data + ['program_id' => $program->id];
        try {
            $programApproval = $this->programApprovalService->createProgramApprovalStep($data);
            return response($programApproval);
        } catch (\Exception $e) {
            return response(['errors' => $e->getMessage(), 422]);
        }
    }

    public function update(ProgramApprovalRequest $programApprovalRequest, Organization $organization, Program $program, ProgramApproval $programApproval)
    {
        $data = $programApprovalRequest->validated();
        try {
            $programApproval = $this->programApprovalService->updateProgramApprovalStep($programApproval, $data);
            return response($programApproval);
        } catch (\Exception $e) {
            return response(['errors' => $e->getMessage(), 422]);
        }
    }

    public function show(Organization $organization, Program $program, ProgramApproval $programApproval)
    {
        $programApproval = $this->programApprovalService->getProgramApprovalStep($programApproval);
        return response($programApproval);
    }

    public function delete(Organization $organization, Program $program, ProgramApproval $programApproval)
    {
        $deleted = $this->programApprovalService->deleteProgramApprovalStep($programApproval);
        return response([$deleted]);
    }

    public function assign(ProgramApprovalAssignmentRequest $programApprovalAssignRequest, Organization $organization, Program $program, ProgramApproval $programApproval)
    {
        $data = $programApprovalAssignRequest->validated();
        return response( (new \App\Services\ProgramApprovalService)->assign($programApproval, $data) );
    }

    public function unassign(ProgramApprovalAssignmentRequest $programApprovalAssignRequest, Organization $organization, Program $program, ProgramApproval $programApproval)
    {
        $data = $programApprovalAssignRequest->validated();
        return response( (new \App\Services\ProgramApprovalService)->unassign($programApproval, $data) );
    }
}
