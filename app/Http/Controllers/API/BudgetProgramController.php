<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\BudgetType;
use App\Models\Organization;
use App\Models\BudgetProgram;
use App\Services\BudgetProgramService;
use App\Models\BudgetCascadingApproval;
use App\Http\Requests\BudgetProgramRequest;
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
        //return response(BudgetProgram::all());
        $budgetPrograms = BudgetProgram::where('program_id', $program->id)
            ->with('budget_types')
            ->get();
        return response($budgetPrograms);
    }

    public function store(BudgetProgramRequest $budgetProgramRequest, Organization $organization, Program $program)
    {
        $data = $budgetProgramRequest->validated();
        $data = $data + ['program_id' => $program->id];
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

    public function getBudgetCascadingApproval(Organization $organization, Program $program)
    {
        $cascadingApproval = BudgetCascadingApproval::where('program_id', $program->id)
            ->get();
        return response($cascadingApproval);
    }

    public function acceptBudgetCascadingApproval(Organization $organization, Program $program)
    {
    }
    
    public function rejectBudgetCascadingApproval(Organization $organization, Program $program)
    {
    }

    public function getCurrentBudget(Organization $organization, Program $program)
    {
        $currentBudget = $this->budgetProgramService->getCurrentBudget($program);
        return response($currentBudget);
    }


    public function downloadAssignBudgetTemplate(Organization $organization, Program $program, BudgetProgram $budgetProgram)
    {
        //return response()->stream(...($this->budgetProgramService->getManageBudgetTemplateCSVStream($program, $budgetProgram)));
    }
}
