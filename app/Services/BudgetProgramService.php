<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\BudgetProgram;
use App\Models\BudgetCascading;
use Illuminate\Support\Facades\Log;
use App\Models\Program;
use RuntimeException;
use Exception;
use Carbon\Carbon;

class BudgetProgramService
{
    public function getAllBudgetTypes()
    {
        return BudgetProgram::all();
    }

    public function getBudgetProgramById($id)
    {
        return BudgetProgram::findOrFail($id);
    }

    public function createBudgetProgram(array $data)
    {
        try {
            $existingBudget = BudgetProgram::where('budget_start_date', '<=', $data['budget_end_date'])
                ->where('budget_end_date', '>=', $data['budget_start_date'])
                ->where('budget_type_id', $data['budget_type_id'])
                ->where('program_id', $data['program_id'])
                ->first();

            if ($existingBudget) {
                throw new \Symfony\Component\HttpKernel\Exception\HttpException(403, 'Budget already exists for the selected duration');
            }
            if ($data['budget_amount'] <= 0) {
                throw new \Symfony\Component\HttpKernel\Exception\HttpException(403, 'Budget Amount should be grater than 0');
            }

            return BudgetProgram::create([
                'budget_type_id' => $data['budget_type_id'],
                'program_id' => $data['program_id'],
                'budget_amount' => $data['budget_amount'],
                'remaining_amount' => $data['budget_amount'],
                'budget_start_date' => $data['budget_start_date'],
                'budget_end_date' => $data['budget_end_date'],
            ]);
        } catch (\Exception $e) {
            throw $e;
        }
    }

    public function updateBudgetProgram(BudgetProgram $budgetProgram, array $data)
    {
        return $budgetProgram->update($data);
    }

    public function getBudgetProgram(BudgetProgram $budgetProgram)
    {
        return $budgetProgram->load('budget_types');
    }

    public function closeBudget(BudgetProgram $budgetProgram)
    {
        $budgetProgram->status = 0;
        $budgetProgram->save();
        return $budgetProgram;
    }

    public function assignBudget(Program $program, BudgetProgram $budgetProgram, array $data)
    {
        $budgetProgramId = $data['budget_program_id'];
        $budgetAmounts = $data['budget_amount'];
        $processedBudgets = [];
        
        foreach ($budgetAmounts as $programData) {
            $programId = $programData['program_id'];
            $budgets = $programData['budgets'];
            foreach ($budgets as $budget) {
                $budgetsCascadingId = $budget['budgets_cascading_id'];
                $year = $budget['year'];
                $month = $budget['month'];
                $amount = $budget['amount'];
                $budgetStartDate = "$year-$month-01";
                $budgetEndDate = date("Y-m-t", strtotime($budgetStartDate)); // Get the last day of the month
    
                if (empty($amount) || $amount === 0) {
                    // Delete the budget record if the amount is zero or not provided
                    BudgetCascading::where('id', $budgetsCascadingId)
                        ->where('program_id', $programId)
                        ->where('budget_start_date', $budgetStartDate)
                        ->where('budget_end_date', $budgetEndDate)
                        ->delete();
                } else {
                    // Update or create the budget record
                    $budgetRecord = BudgetCascading::updateOrCreate(
                        [
                            'id' => $budgetsCascadingId,
                            'budget_start_date' => $budgetStartDate,
                            'budget_end_date' => $budgetEndDate
                        ],
                        [
                            'parent_program_id' => $program->id,
                            'program_id' => $programId,
                            'budget_program_id' => $budgetProgramId,
                            'budget_amount_remaining' => $amount,
                            'budget_amount' => $amount,
                            'reason_for_budget_change' => "assign budget"
                        ]
                    );
    
                    $processedBudgets[] = $budgetRecord;
                }
            }
        }
    
        return $processedBudgets;
    }
    

    public function updateRemainingBudget($programBudgetId, $assignedBudget, $availableBudget)
    {
        if ($programBudgetId && ($assignedBudget || $availableBudget)) {
            $budgetProgram = BudgetProgram::find($programBudgetId);
            if (!$budgetProgram) {
                throw new \RuntimeException('Budget program not found.');
            }

            $budgetProgram->remaining_amount = $budgetProgram->remaining_amount - $assignedBudget + $availableBudget;
            if (!$budgetProgram->save()) {
                throw new \RuntimeException('Failed to update remaining amount.');
            }
        }
    }

    public function deleteBudgetRows($programBudgetId, $programBudgetsIds, $args = [])
    {
        $budgetAmounts = [];
        $budgetsCascadingIds = [];

        foreach ($programBudgetsIds as $programId => $programBudgetsId) {
            // Ensure that $programBudgetsId is an array
            if (!is_array($programBudgetsId) || empty($programBudgetsId)) {
                continue;
            }

            if ($args['budget_type'] == 1) {
                $budgetsCascadingIds = array_merge($budgetsCascadingIds, array_column($programBudgetsId, 'budgets_cascading_id'));
                $budgetAmounts = array_merge($budgetAmounts, array_column($programBudgetsId, 'budget_amount'));
            } else {
                $budgetsCascadingIds = array_merge($budgetsCascadingIds, array_column($programBudgetsIds, 'budgets_cascading_id'));
                $budgetAmounts = array_merge($budgetAmounts, array_column($programBudgetsIds, 'budget_amount'));
            }
        }

        if (!empty($budgetsCascadingIds)) {
            $deletedRows = BudgetCascading::whereIn('id', $budgetsCascadingIds)->delete();
            if (!$deletedRows) {
                throw new \RuntimeException('Failed to delete budget rows.');
            }
        }

        return array_sum($budgetAmounts);
    }
}
