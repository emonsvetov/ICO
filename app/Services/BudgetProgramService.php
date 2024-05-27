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
        try {
            DB::beginTransaction();

            $budgetType = $data['budget_type'];
            $budgetAmounts = $data['budget_amount'];
            // $remainingBudgets = $data['remaining_budgets'];
            //  $availableBudget = $assignedBudget = 0;

            foreach ($budgetAmounts as $programData) {
                $programId = $programData['program'];
                $budgets = $programData['budgets'];
                foreach ($budgets as $budget) {
                    $budgets_cascading_id = $budget['budgets_cascading_id'];
                    $year = $budget['year'];
                    $month = $budget['month'];
                    $amount = $budget['amount'];
                    $budget_start_date = "$year-$month-01";
                    $budget_end_date = "$year-$month-30";
                    if ($amount === 0 || empty($amount)) {
                        // Delete the budget record if the amount is zero or not provided
                        BudgetCascading::where('budgets_cascading_id', $budgets_cascading_id)
                            ->where('program_id', $programId)
                            ->where('budget_start_date', $budget_start_date)
                            ->where('budget_end_date', $budget_end_date)
                            ->delete();
                    } else {
                        $existingBudget = BudgetCascading::where('budgets_cascading_id', $budgets_cascading_id)
                            ->where('budget_start_date', $budget_start_date)
                            ->where('budget_end_date', $budget_end_date)
                            ->first();

                        if ($existingBudget) {
                            // Update the existing budget record
                            $existingBudget->budget_amount = $amount;
                            $existingBudget->budget_amount_remaining = $amount;
                            $existingBudget->save();
                        } else {
                            // Create a new budget record
                            BudgetCascading::create([
                                'parent_program_id' => $program->id,
                                'program_id' => $programId,
                                'program_budget_id' => $budgetProgram->id,
                                'budget_amount_remaining' => $amount,
                                'budget_start_date' => $budget_start_date,
                                'budget_end_date' => $budget_end_date,
                                'budget_amount' => $amount,
                                'reason_for_budget_change' => "assign budget"
                            ]);
                        }
                    }
                }
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
