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
		return response()->json(['data' => $types], 200);
	}
    
   public function store(BudgetProgramRequest $budgetProgramRequest, Organization $organization, Program $program)
    {
		 $data = $budgetProgramRequest->validated();
         $data = $data + ['program_id' => $program->id];
        try {
            $budgetProgram = $this->budgetProgramService->createBudgetProgram($data);
            return response()->json(['message' => 'Budget program created successfully', 'data' => $budgetProgram], 201);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getStatusCode());
        }
	}
}
