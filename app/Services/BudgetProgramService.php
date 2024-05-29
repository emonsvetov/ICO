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

    public function assignBudget(Program $program, BudgetProgram $budgetProgram, array $data)
    {
        $budgetProgramId = $budgetProgram->id;
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
                            'program_budget_id' => $budgetProgramId,
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

    public function getMonths($budgetProgramId)
    {
        $_budgetProgram = BudgetProgram::find($budgetProgramId);
        if ($_budgetProgram) {
            $startDate = Carbon::parse($_budgetProgram->budget_start_date);
            $endDate = Carbon::parse($_budgetProgram->budget_end_date);

            $months = [];
            while ($startDate->lessThanOrEqualTo($endDate)) {
                $months[] = $startDate->format('F');
                $startDate->addMonth();
            }
            $data[] = [
                'months' => $months,
            ];
            return $data;
        }

    }

    public function getManageBudgetDataByProgram(Program $program, BudgetCascading $budgetCascading)
    {
        $topLevelProgram = $program->rootAncestor()->select(['id', 'name'])->first();
        if (!$topLevelProgram) {
            $topLevelProgram = $program;
        }
        $programs = $topLevelProgram->descendantsAndSelf()->depthFirst()->whereNotIn('id', [$program->id])->select(['id', 'name'])->get();
        $amount = BudgetCascading::all();

        return
            [
                'program' => $program,
                'programs' => $programs,
                'amount' => $amount->budget_amount,
            ]
        ;
    }

    public function getManageBudgetTemplateCSV(Program $program, BudgetProgram $budgetProgram)
    {
        //$manageBudgetData = (object) $this->//make a function for set manage data;
        $csv = array();

        $months = $this->getMonths($budgetProgram->id);
        // Add the section for the transfer from
        $csvManageBudgetFromRow = self::ASSIGN_BUDGET_CSV_FROM_HEADER;
        $csv[] = $csvManageBudgetFromRow; //csv header row
        $csv[] = [$budgetProgram->id, $budgetProgram->remaining_amount, $budgetProgram->budget_start_date, $budgetProgram->budget_end_date];

        foreach ($months as $month) {
            $monthRow = [$budgetProgram->id, $budgetProgram->remaining_amount, $month];
            $csv[] = $monthRow;
        }
        ;
        $csvManageBudgetToRow = self::ASSIGN_BUDGET_CSV_TO_HEADER;
        $csv[] = $csvManageBudgetToRow;
        $csv[] = [1, "incentco", 0];
        // if ($manageBudgetData->programs->isNotEmpty()) {
        //     foreach ($transferData->programs as $_program) {
        //         if ($_program->id == $program->id) { //in case
        //             continue;
        //         }
        //         $programToRow = [$_program->id, $_program->external_id, $_program->name, 0];
        //         $csv[] = $programToRow;
        //     }
        // }
        return $csv;
    }

    public function getManageBudgetTemplateCSVStream(Program $program, BudgetProgram $budgetProgram)
    {
        $csv = $this->getManageBudgetTemplateCSV($program, $budgetProgram);
        $csvFilename = 'assign-budget-template-';

        $headers = array(
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$csvFilename",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        );

        $callback = function () use ($csv) {
            $file = fopen('php://output', 'w');

            foreach ($csv as $row) {
                // Flatten any nested arrays
                $row = $this->flattenRow($row);
                fputcsv($file, $row);
            }
            fclose($file);
        };
        return [$callback, 200, $headers];
    }

    private function flattenRow($row)
    {
        $flattenedRow = [];
        foreach ($row as $item) {
            if (is_array($item)) {
                // Flatten nested array
                $flattenedRow[] = implode(', ', $item['months']);
            } else {
                $flattenedRow[] = $item;
            }
        }
        return $flattenedRow;
    }
}
