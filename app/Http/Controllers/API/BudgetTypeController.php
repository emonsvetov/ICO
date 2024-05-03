<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\BudgetType;
use App\Models\Organization;
use App\Services\BudgetTypeService;
use App\Http\Requests\BudgetTypeRequest;
use Illuminate\Http\Request;

class BudgetTypeController extends Controller
{

	protected $budgetTypeService;

    public function __construct(BudgetTypeService $budgetTypeService)
    {
        $this->budgetTypeService = $budgetTypeService;
    }

   public function store(BudgetTypeRequest $budgetTypeRequest, Organization $organization, Program $program)
    {
		 $data = $budgetTypeRequest->validated();
        try {
            $budgetType = $this->budgetTypeService->createBudgetType($data);
            return response()->json(['message' => 'Budget Type created successfully', 'data' => $budgetType], 201);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getStatusCode());
        }
	}
}
