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
    public function getTransferMoniesByProgram(Program $program)
    {
        $topLevelProgram = $program->rootAncestor()->select(['id', 'name', 'external_id'])->first();
        if (!$topLevelProgram) {
            $topLevelProgram = $program;
        }
        $programs = $topLevelProgram->descendantsAndSelf()->depthFirst()->whereNotIn('id', [$program->id])->select(['id', 'name', 'external_id'])->get();
        $balance = (new \App\Services\AccountService)->readAvailableBalanceForProgram($program);
        return
            [
                'program' => $program,
                'programs' => $programs,
            ]
        ;
    }

    public function getMonths($budgetProgram)
    {
        $startDate = Carbon::parse($budgetProgram->budget_start_date);
        $endDate = Carbon::parse($budgetProgram->budget_end_date);

        $months = [];
        while ($startDate->lessThan($endDate)) {
            $months[] = $startDate->format('F');
            $startDate->addMonth();
        }

        $data = [
            'months' => $months,
        ];
        return $data;
    }

    public function getAssignBudgetByProgram(Program $program)
    {
        $program = Program::find($program->id);
        $programsId = $program->descendantsAndSelf()->get()->pluck('id')->toArray();

        $minimalFields = Program::MIN_FIELDS;
        $query = Program::query();
        $query->where('parent_id', $program->parent_id);
        $query->whereIn('id', $programsId);
        $query = $query->select($minimalFields);
        $query = $query->with([
            'childrenMinimal' => function ($query) use ($minimalFields) {
                $subquery = $query->select($minimalFields);
                return $subquery;
            }
        ]);
        $result = $query->get();
        return childrenizeCollection($result);
        ;
    }
    public function getAssignBudgetTemplateCSV(Organization $organization, Program $program,ProgramService $programService, BudgetProgram $budgetProgram)
    {
       // $programdata = $this->getAssignBudgetByProgram($program);
        $transferData = $programService->getHierarchyByProgramId($organization, $program->id)->toArray();
        dd($transferData);
        $csv = array();
        $months = $this->getMonths($budgetProgram);
        $csvTransferFromRow = self::ASSIGN_BUDGET_CSV_FROM_HEADER;
        $csv[] = $csvTransferFromRow; //csv header row
        $budgetTypeId = $budgetProgram->budget_type_id;
        $budgetType = BudgetType::findOrFail($budgetTypeId);
        $csvAssignBudgetRow = self::ASSIGN_BUDGET_CSV_TO_HEADER;
        $csv[] = [$budgetProgram->budget_amount, $budgetProgram->remaining_amount, $budgetType->title, $budgetProgram->budget_start_date, $budgetProgram->budget_end_date]; //
        foreach ($months as $month) {
            $csvTransferToRow = [...$csvAssignBudgetRow, ...$month];
        }
        $csv[] = $csvTransferToRow;
        if ($transferData) {
            foreach ($transferData as $_program) {
              
                // if ($_program->id == $program->id) {
                //     continue;
                // }
                //    $programToRow = [$_program->id, $_program->external_id, $_program->name, 0];
                //  $csv[] = $programToRow;
            }
        }
        $programToRow = [1, "incentco", 0];
        $csv[] = $programToRow;
        return $csv;
    }

    public function getAssignBudgetTemplateCSVStream(Program $program, BudgetProgram $budgetProgram)
    {
        $months = $this->getMonths($budgetProgram);
        $csv = $this->getAssignBudgetTemplateCSV($program, $budgetProgram);
        $csvFilename = 'transfer-template-' . $program->id;

        $headers = array(
            "Content-type" => "text/csv",
            "Content-Disposition" => "attachment; filename=$csvFilename",
            "Pragma" => "no-cache",
            "Cache-Control" => "must-revalidate, post-check=0, pre-check=0",
            "Expires" => "0"
        );

        $callback = function () use ($csv, $months) {
            $file = fopen('php://output', 'w');
            foreach ($csv as $row) {
                $row = $this->flattenRow($row, count($months));
                fputcsv($file, $row);
            }
            fclose($file);
        };
        return [$callback, 200, $headers];
    }
    private function flattenRow($row, $monthCount)
    {
        $flattenedRow = [];
        foreach ($row as $key => $item) {
            if (is_array($item) && isset($item['months'])) {
                // Add months to the flattened row
                $flattenedRow = array_merge($flattenedRow, $item['months']);
                // If fewer months are provided, fill the rest with empty values
                while (count($flattenedRow) < $monthCount) {
                    $flattenedRow[] = '';
                }
            } else {
                $flattenedRow[] = $item;
            }
        }

        return $flattenedRow;
    }
}
