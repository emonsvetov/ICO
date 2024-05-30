<?php

namespace App\Services;

use App\Models\Organization;
use Illuminate\Support\Facades\DB;
use App\Models\BudgetProgram;
use App\Models\BudgetType;
use App\Models\BudgetCascading;
use App\Services\ProgramService;
use Illuminate\Support\Facades\Log;
use App\Models\Program;
use RuntimeException;
use Exception;
use Carbon\Carbon;

class BudgetProgramService
{
    const ASSIGN_BUDGET_CSV_FROM_HEADER = ["Total Budget", "Remaining Budget", "Budget Type", "Budget Start Date", "Budget End Date"];
    const ASSIGN_BUDGET_CSV_TO_HEADER = ["Assign Budget to Program Id", "Assign Budget to program Name"];
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
        $total_amount = $budgetProgram->budget_amount;
        $rem_amount = $budgetProgram->remaining_amount;

        $budgetProgramId = $budgetProgram->id;
        $parent_program_id = $data['parent_program_id'];
        $budgetAmounts = $data['budget_amount'];
        $processedBudgets = [];

        if ($data['budget_type'] == '1') {
            foreach ($budgetAmounts as $programData) {
                $programId = $programData['program_id'];
                $budgets = $programData['budgets'];
                foreach ($budgets as $budget) {
                    $budgetsCascadingId = $budget['budgets_cascading_id'];
                    $year = $budget['year'];
                    $month = $budget['month'];
                    $amount = $budget['amount'];

                    $budgetStartDate = "$year-$month-01";
                    $budgetEndDate = date("Y-m-t", strtotime($budgetStartDate));

                    if (empty($amount) || $amount === 0 && !empty($budgetsCascadingId)) {
                        // Delete the budget record if the amount is zero or not provided
                        BudgetCascading::where('id', $budgetsCascadingId)
                            ->where('program_id', $programId)
                            ->where('budget_start_date', $budgetStartDate)
                            ->where('budget_end_date', $budgetEndDate)
                            ->delete();
                    } else {
                        // Calculate the updated amount
                        $updated_amount = $total_amount - $amount;

                        if ($updated_amount < 0) {
                            return response()->json(['error' => 'You cannot assign Budget more than you have available.'], 422);
                        }

                        // Update or create the budget record
                        $budgetRecord = BudgetCascading::updateOrCreate(
                            [
                                'id' => $budgetsCascadingId,
                                'budget_start_date' => $budgetStartDate,
                                'budget_end_date' => $budgetEndDate
                            ],
                            [
                                'parent_program_id' => $parent_program_id,
                                'program_id' => $programId,
                                'budget_program_id' => $budgetProgramId,
                                'budget_amount_remaining' => $amount,
                                'budget_amount' => $amount,
                                'reason_for_budget_change' => "assign budget"
                            ]
                        );

                        $processedBudgets[] = $budgetRecord;

                        // Update the remaining amount
                        $budgetProgram->remaining_amount = $updated_amount;
                        $budgetProgram->save();

                        // Update the total amount for the next iteration
                        $total_amount = $updated_amount;
                    }
                }
            }
        } else {
            foreach ($budgetAmounts as $programData) {
                $programId = $programData['program_id'];
                $budgetsCascadingId = $programData['budgets_cascading_id'];
                $budgetStartDate = $programData['budget_start_date'];
                $budgetEndDate = $programData['budget_end_date'];
                $amount = $programData['amount'];

                if (empty($amount) || $amount === 0 && !empty($budgetsCascadingId)) {
                    // Delete the budget record if the amount is zero or not provided
                    BudgetCascading::where('id', $budgetsCascadingId)
                        ->where('program_id', $programId)
                        ->where('budget_start_date', $budgetStartDate)
                        ->where('budget_end_date', $budgetEndDate)
                        ->delete();
                } else {
                    // Calculate the updated amount
                    $updated_amount = $total_amount - $amount;

                    if ($updated_amount < 0) {
                        return response()->json(['error' => 'You cannot assign Budget more than you have available.'], 422);
                    }

                    // Update or create the budget record
                    $budgetRecord = BudgetCascading::updateOrCreate(
                        [
                            'id' => $budgetsCascadingId,
                            'budget_start_date' => $budgetStartDate,
                            'budget_end_date' => $budgetEndDate
                        ],
                        [
                            'parent_program_id' => $parent_program_id,
                            'program_id' => $programId,
                            'budget_program_id' => $budgetProgramId,
                            'budget_amount_remaining' => $amount,
                            'budget_amount' => $amount,
                            'reason_for_budget_change' => "assign budget"
                        ]
                    );
                    $processedBudgets[] = $budgetRecord;

                    // Update the remaining amount
                    $budgetProgram->remaining_amount = $updated_amount;
                    $budgetProgram->save();

                    // Update the total amount for the next iteration
                    $total_amount = $updated_amount;
                }
            }
        }
        return $processedBudgets;
    }




    // public function updateRemainingBudget($programBudgetId, $assignedBudget, $availableBudget)
    // {
    //     if ($programBudgetId && ($assignedBudget || $availableBudget)) {
    //         $budgetProgram = BudgetProgram::find($programBudgetId);
    //         if (!$budgetProgram) {
    //             throw new \RuntimeException('Budget program not found.');
    //         }

    //         $budgetProgram->remaining_amount = $budgetProgram->remaining_amount - $assignedBudget + $availableBudget;
    //         if (!$budgetProgram->save()) {
    //             throw new \RuntimeException('Failed to update remaining amount.');
    //         }
    //     }
    // }

    // public function deleteBudgetRows($programBudgetId, $programBudgetsIds, $args = [])
    // {
    //     $budgetAmounts = [];
    //     $budgetsCascadingIds = [];

    //     foreach ($programBudgetsIds as $programId => $programBudgetsId) {
    //         // Ensure that $programBudgetsId is an array
    //         if (!is_array($programBudgetsId) || empty($programBudgetsId)) {
    //             continue;
    //         }

    //         if ($args['budget_type'] == 1) {
    //             $budgetsCascadingIds = array_merge($budgetsCascadingIds, array_column($programBudgetsId, 'budgets_cascading_id'));
    //             $budgetAmounts = array_merge($budgetAmounts, array_column($programBudgetsId, 'budget_amount'));
    //         } else {
    //             $budgetsCascadingIds = array_merge($budgetsCascadingIds, array_column($programBudgetsIds, 'budgets_cascading_id'));
    //             $budgetAmounts = array_merge($budgetAmounts, array_column($programBudgetsIds, 'budget_amount'));
    //         }
    //     }

    //     if (!empty($budgetsCascadingIds)) {
    //         $deletedRows = BudgetCascading::whereIn('id', $budgetsCascadingIds)->delete();
    //         if (!$deletedRows) {
    //             throw new \RuntimeException('Failed to delete budget rows.');
    //         }
    //     }

    //     return array_sum($budgetAmounts);
    // }
    public function getManageBudgetTemplateCSVStream(Program $program, BudgetProgram $budgetProgram)
    {
        //templete
    }
}
