<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\BudgetType;
use App\Models\Organization;
use App\Models\BudgetProgram;
use App\Services\BudgetProgramService;
use App\Http\Requests\BudgetProgramRequest;
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
        $budgetPrograms = BudgetProgram::with('budget_types')->get();
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
            return response(['errors' => $e->getMessage(), 422]);
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
}