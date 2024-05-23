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

    public function assignBudget(BudgetProgram $budgetProgram, array $data)
    {
        try {
            DB::beginTransaction();
        
            $budgetType = $data['budget_type'];
            $budgetAmount = $data['budget_amount'];
            $parentProgramId = $data['parent_program_id'];
            $programBudgetId = $data['program_budget_id'];
            $programBudgetsIds = $data['program_budgets_ids'];
            $remainingBudgets = $data['remaining_budgets'];
            $externalIds = $data['external_ids'];
            $programBudgetAmounts = $data['program_budget_amounts'];
        
            $availableBudget = $assignedBudget = 0;

            foreach ($budgetAmount as $programId => $budgetData) {
                foreach ($budgetData as $key => $budget) {
                    if (isset($programBudgetsIds[$programId][$key]) && isset($programBudgetsIds[$programId][$key]['budgets_cascading_id'])) {
                        $budgetsCascading = BudgetCascading::find($programBudgetsIds[$programId][$key]['budgets_cascading_id']);
        
                        if ($budget === $programBudgetAmounts[$programId][$key]) {
                            unset($programBudgetsIds[$programId][$key]);
                            continue;
                        }
        
                        $remainingBudgetAmount = $remainingBudgets[$programId][$key];
        
                        if ($budget < 0 || $remainingBudgetAmount < 0) {
                            throw new RuntimeException('Budget amount should not be less than 0');
                        }
        
                        $budgetsCascading->budget_amount = $budget;
                        $budgetsCascading->budget_amount_remaining = $remainingBudgetAmount;
                        $budgetsCascading->reason_for_budget_change = 'Updated From Manage Budget';
                        $budgetsCascading->save();
        
                        if ($budget > $programBudgetsIds[$programId][$key]['budget_amount']) {
                            $assignedBudget += ($budget - $programBudgetsIds[$programId][$key]['budget_amount']);
                        } elseif ($budget < $programBudgetsIds[$programId][$key]['budget_amount']) {
                            $availableBudget += ($programBudgetsIds[$programId][$key]['budget_amount'] - $budget);
                        }
        
                        unset($programBudgetsIds[$programId][$key]);
                    } else {
                        // Insert new budget cascading
                        $yearMonth = str_replace('_', '-', $key);
                        $budgetStartDate = date($yearMonth . '-01');
                        $budgetEndDate = date($yearMonth . '-' . date('t', strtotime($budgetStartDate)));
        
                        $budgetsCascading = new BudgetCascading([
                            'sub_program_external_id' => $externalIds[$programId],
                            'budget_amount' => $budget,
                            'budget_amount_remaining' => $budget,
                            'parent_program_id' => $parentProgramId,
                            'program_id' => $programId,
                            'program_budget_id' => $programBudgetId,
                            'budget_start_date' => $budgetStartDate,
                            'budget_end_date' => $budgetEndDate,
                            'reason_for_budget_change' => 'Assigned Budgets'
                        ]);
        
                        $budgetsCascading->save();
                        $assignedBudget += $budget;
                    }
                }
            }
        
            // Assume delete_budget_rows and update_remaining_budget are methods in the current class
        
            // Delete the rows if the budget amount has been removed
            if (count($programBudgetsIds) > 0) {
                $availableBudget += $this->deleteBudgetRows($programBudgetId, $programBudgetsIds, ['budget_type' => $budgetType]);
            }
        
            // After insert or update in budget_programs, update the remaining amount in budget_programs
            if ($assignedBudget > 0 || $availableBudget > 0) {
                $this->updateRemainingBudget($programBudgetId, $assignedBudget, $availableBudget);
            }
        
            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error processing budget data', ['error' => $e->getMessage()]);
            throw new RuntimeException($e->getMessage(), 500);
        }
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
            $deletedRows = BudgetCascading::whereIn('budgets_cascading_id', $budgetsCascadingIds)->delete();
            if (!$deletedRows) {
                throw new \RuntimeException('Failed to delete budget rows.');
            }
        }

        return array_sum($budgetAmounts);
    }
}
