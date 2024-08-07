<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\BudgetType;
use App\Models\Organization;
use App\Models\BudgetProgram;
use App\Models\BudgetCascading;
use App\Services\BudgetProgramService;
use App\Models\BudgetCascadingApproval;
use App\Http\Requests\BudgetProgramRequest;
use App\Http\Requests\BudgetCascadinApprovalRequest;
use App\Http\Requests\BudgetProgramAssignRequest;
use Illuminate\Http\Request;

class BudgetProgramController extends Controller
{

    protected $budgetProgramService;

    public function __construct(BudgetProgramService $budgetProgramService)
    {
        $this->budgetProgramService = $budgetProgramService;
    }

    public function getBudgetTypes()
    {
        $types = BudgetType::budgetTypeList();
        return response($types);
    }

    public function index(Organization $organization, Program $program)
    {
        $p_program = new Program();
        $program_id = $p_program->get_top_level_program_id($program->id);
        $budgetPrograms = BudgetProgram::where('program_id', $program_id)
            ->with('budget_types')
            ->get();
        return response($budgetPrograms);
    }

    public function store(BudgetProgramRequest $budgetProgramRequest, Organization $organization, Program $program)
    {
        if ($program->parent_id != NULL) {
            $p_id = $program->parent_id;
        } else {
            $p_id = $program->id;
        }
        $data = $budgetProgramRequest->validated();
        $data = $data + ['program_id' => $p_id];
        try {
            $budgetProgram = $this->budgetProgramService->createBudgetProgram($data);
            return response($budgetProgram);
        } catch (\Exception $e) {
            return response(['errors' => $e->getMessage()], 422);
        }
    }

    public function update(BudgetProgramRequest $budgetProgramRequest, Organization $organization, Program $program, BudgetProgram $budgetProgram)
    {
        $data = $budgetProgramRequest->validated();
        $budgetProgram = $this->budgetProgramService->updateBudgetProgram($budgetProgram, $data);
        return response($budgetProgram);
    }

    public function show(Organization $organization, Program $program, BudgetProgram $budgetProgram)
    {
        $budgetProgram = $this->budgetProgramService->getBudgetProgram($budgetProgram);
        return response($budgetProgram);
    }

    public function close(Organization $organization, Program $program, BudgetProgram $budgetProgram)
    {
        $budgetProgram = $this->budgetProgramService->closeBudget($budgetProgram);
        return response($budgetProgram);
    }

    public function assign(BudgetProgramAssignRequest $budgetProgramAssignRequest, Organization $organization, Program $program, BudgetProgram $budgetProgram)
    {
        $data = $budgetProgramAssignRequest->validated();
        $data = $data + ['parent_program_id' => $program->id];
        $budgetProgram = $this->budgetProgramService->assignBudget($budgetProgram, $data);
        return response($budgetProgram);
    }

    public function getBudgetCascading(Organization $organization, Program $program, BudgetProgram $budgetProgram)
    {
        $budgetProgram = $this->budgetProgramService->getBudgetCascading($budgetProgram, $program);
        return response($budgetProgram);
    }

    public function getBudgetCascadingApproval(Organization $organization, Program $program, string $title, Request $request)
    {
        $getBudgetProgram = $this->budgetProgramService->getBudgetCascadingApproval($program, $title, $request);
        return response($getBudgetProgram);
    }

    public function acceptRejectBudgetCascadingApproval(BudgetCascadinApprovalRequest $approvalRequest, Organization $organization, Program $program, BudgetCascadingApproval $budgetCascadinApproval)
    {
        $data = $approvalRequest->validated();
        $budgetCascadingApprovals = $this->budgetProgramService->acceptRejectBudgetCascadingApproval($data);
        return response($budgetCascadingApprovals);
    }

    public function revokeBudgetCascadingApproval(BudgetCascadinApprovalRequest $approvalRequest, Organization $organization, Program $program, BudgetCascadingApproval $budgetCascadingApproval)
    {
        $data = $approvalRequest->validated();
        $revokeBudget = $this->budgetProgramService->revokeBudgetCascadingApproval($data);
        return response($revokeBudget);
    }

    public function getCurrentBudget(Organization $organization, Program $program)
    {
        $currentBudget = $this->budgetProgramService->getCurrentBudget($organization, $program);
        return response($currentBudget);
    }

    public function getPendingCascadingApproval(Organization $organization, Program $program, BudgetCascadingApproval $budgetCascadingApproval, $participant)
    {
        $budgetCascadingPendingData = $this->budgetProgramService->getPendingCascadingApproval($participant);
        return response($budgetCascadingPendingData);
    }

    public function awardsPending(Organization $organization, Program $program, BudgetCascadingApproval $budgetCascadingApproval)
    {
        $awardsPending = $this->budgetProgramService->awardsPending($program);
        return response($awardsPending);
    }

    public function manageScheduleDate(BudgetCascadinApprovalRequest $approvalRequest, Organization $organization, Program $program, BudgetCascadingApproval $budgetCascadinApproval)
    {
        $data = $approvalRequest->validated();
        $ScheduleDate = $this->budgetProgramService->manageScheduleDate($data);
        return response($ScheduleDate);
    }

    public function downloadAssignBudgetTemplate(Organization $organization, Program $program, BudgetProgram $budgetProgram)
    {
        //return response()->stream(...($this->budgetProgramService->getManageBudgetTemplateCSVStream($program, $budgetProgram)));
    }
}
