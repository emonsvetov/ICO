<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\BudgetProgram;
use App\Models\BudgetCascading;
use App\Models\Program;
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
            $budgetType = $data['budget_type'];
            $budgetAmount = $data['budget_amount'];
            $parentProgramId = $data['program_id'];
            $programBudgetId = $budgetProgram->id;
            $programBudgetsIds = $data['program_budgets_ids'];
            $remainingBudgets = $data['remaining_budgets'];
            $externalIds = $data['external_ids'];
            $programBudgetAmounts = $data['program_budget_amounts'];
            $availableBudget = $assignedBudget = 0;
            $values = [];
            dd($budgetAmount);
            if ($budgetType == 1) {
                foreach ($budgetAmount as $programId => $budgetWithMonths) {


                    foreach ($budgetWithMonths as $key => $budget) {
                        if (isset($programBudgetsIds[$programId][$key])) {
                            if ($budget == $programBudgetAmounts[$programId][$key]) {
                                unset($programBudgetsIds[$programId][$key]);
                                continue;
                            }
                            $budgetsCascadingId = $programBudgetsIds[$programId][$key]['budgets_cascading_id'];
                            $remainingBudgetAmount = $remainingBudgets[$programId][$key];
    
                            if ($budget < 0 || $remainingBudgetAmount < 0) {
                                throw new \RuntimeException('Budget amount or Remaining amount should not be less than 0');
                            }
    
                            DB::table('budgets_cascading')->where('budgets_cascading_id', $budgetsCascadingId)->update([
                                'budget_amount' => $budget,
                                'budget_amount_remaining' => $remainingBudgetAmount,
                                'created_at' => now(),
                                'updated_at' => now(),
                                'reason_for_budget_change' => 'Updated From Manage Budget'
                            ]);
    
                            if ($budget > $programBudgetsIds[$programId][$key]['budget_amount']) {
                                $assignedBudget += ($budget - $programBudgetsIds[$programId][$key]['budget_amount']);
                            } else if ($budget < $programBudgetsIds[$programId][$key]['budget_amount']) {
                                $availableBudget += ($programBudgetsIds[$programId][$key]['budget_amount'] - $budget);
                            }
                            unset($programBudgetsIds[$programId][$key]);
                        } else {
                            $externalId = $externalIds[$programId];
                            $yearMonth = str_replace('_', '-', $key);
    
                            $values[] = [
                                'sub_program_external_id' => $externalId,
                                'budget_amount' => $budget,
                                'budget_amount_remaining' => $budget,
                                'parent_program_id' => $parentProgramId,
                                'program_id' => $programId,
                                'program_budget_id' => $programBudgetId,
                                'budget_start_date' => '',
                                'budget_end_date' => '',
                                'reason_for_budget_change' => 'Assigned Budgets'
                            ];
    
                            $assignedBudget += $budget;
                        }
                    }
                }
            } else {
                foreach ($budgetAmount as $programId => $budget) {
                    if (isset($programBudgetsIds[$programId])) {
                        if ($budget == $programBudgetAmounts[$programId]) {
                            unset($programBudgetsIds[$programId]);
                            continue;
                        }
                        $budgetsCascadingId = $programBudgetsIds[$programId]['budgets_cascading_id'];
                        $remainingBudgetAmount = $remainingBudgets[$programId];
    
                        if ($budget < 0 || $remainingBudgetAmount < 0) {
                            throw new \RuntimeException('Budget amount or Remaining amount should not be less than 0');
                        }
    
                        DB::table('budgets_cascading')->where('budgets_cascading_id', $budgetsCascadingId)->update([
                            'budget_amount' => $budget,
                            'budget_amount_remaining' => $remainingBudgetAmount,
                            'created_at' => now(),
                            'updated_at' => now(),
                            'reason_for_budget_change' => 'Updated From Manage Budget'
                        ]);
    
                        if ($budget > $programBudgetsIds[$programId]['budget_amount']) {
                            $assignedBudget += ($budget - $programBudgetsIds[$programId]['budget_amount']);
                        } else if ($budget < $programBudgetsIds[$programId]['budget_amount']) {
                            $availableBudget += ($programBudgetsIds[$programId]['budget_amount'] - $budget);
                        }
                        unset($programBudgetsIds[$programId]);
                    } else {
                        $externalId = $externalIds[$programId];
                        $budgetStartDate = Carbon::parse($data['budget_start_date']);
                        $budgetEndDate = Carbon::parse($data['budget_end_date']);
    
                        $values[] = [
                            'sub_program_external_id' => $externalId,
                            'budget_amount' => $budget,
                            'budget_amount_remaining' => $budget,
                            'parent_program_id' => $parentProgramId,
                            'program_id' => $programId,
                            'program_budget_id' => $programBudgetId,
                            'budget_start_date' => $budgetStartDate,
                            'budget_end_date' => $budgetEndDate,
                            'created_at' => now(),
                            'updated_at' => now(),
                            'reason_for_budget_change' => 'Assigned Budgets'
                        ];
    
                        $assignedBudget += $budget;
                    }
                }
            }
    
            if (count($values) > 0) {
                DB::table('budgets_cascading')->insert($values);
            }
    
            if (count($programBudgetsIds) > 0) {
                $availableBudget += $this->deleteBudgetRows($programBudgetId, $programBudgetsIds, ['budget_type' => $budgetType, 'userId' => auth()->id()]);
            }
    
            if ($assignedBudget > 0 || $availableBudget > 0) {
                $this->updateRemainingBudget($programBudgetId, $assignedBudget, $availableBudget);
            }
    
            return $budgetProgram;
        } catch (\Exception $e) {
            throw new \RuntimeException($e->getMessage(), 500);
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
        //dd($args);
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
