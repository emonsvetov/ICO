<?php

namespace App\Services;

use App\Models\Organization;
use Illuminate\Support\Facades\DB;
use App\Models\BudgetProgram;
use App\Models\BudgetType;
use App\Models\BudgetCascading;
use App\Models\BudgetCascadingApproval;
use App\Models\User;
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

                    if ((empty($amount) || $amount === 0) && !empty($budgetsCascadingId)) {
                        $this->deleteAmount($budgetProgram, $programId, $budgetsCascadingId, $budgetStartDate, $budgetEndDate);
                    } else {
                        $processedBudgets[] = $this->updateAmount($budgetProgram, $parent_program_id, $programId, $budgetsCascadingId, $budgetStartDate, $budgetEndDate, $amount);
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

                if ((empty($amount) || $amount === 0) && !empty($budgetsCascadingId)) {
                    $this->deleteAmount($budgetProgram, $programId, $budgetsCascadingId, $budgetStartDate, $budgetEndDate);
                } else {
                    $processedBudgets[] = $this->updateAmount($budgetProgram, $parent_program_id, $programId, $budgetsCascadingId, $budgetStartDate, $budgetEndDate, $amount);
                }
            }
        }

        return $processedBudgets;
    }

    private function updateAmount(BudgetProgram $budgetProgram, $parent_program_id, $programId, $budgetsCascadingId, $budgetStartDate, $budgetEndDate, $amount)
    {
        $existingBudget = BudgetCascading::where('program_id', $programId)
            ->where('budget_start_date', $budgetStartDate)
            ->where('budget_end_date', $budgetEndDate)
            ->first();

        $rem_amount = $budgetProgram->remaining_amount;

        if ($existingBudget) {
            $difference = $amount - $existingBudget->budget_amount;
        } else {
            $difference = $amount;
        }

        $updated_amount = $rem_amount - $difference;

        if ($updated_amount < 0) {
            throw new \Symfony\Component\HttpKernel\Exception\HttpException(422, 'You cannot assign Budget more than you have available.');
        }

        if ($existingBudget) {
            $existingBudget->update([
                'parent_program_id' => $parent_program_id,
                'budget_program_id' => $budgetProgram->id,
                'budget_amount_remaining' => $amount,
                'budget_amount' => $amount,
                'reason_for_budget_change' => "assign budget"
            ]);
            $budgetRecord = $existingBudget;
        } else {
            $budgetRecord = BudgetCascading::create([
                'parent_program_id' => $parent_program_id,
                'program_id' => $programId,
                'budget_program_id' => $budgetProgram->id,
                'budget_start_date' => $budgetStartDate,
                'budget_end_date' => $budgetEndDate,
                'budget_amount_remaining' => $amount,
                'budget_amount' => $amount,
                'reason_for_budget_change' => "assign budget"
            ]);
        }

        $budgetProgram->remaining_amount = $updated_amount;
        $budgetProgram->save();

        return $budgetRecord;
    }

    private function deleteAmount(BudgetProgram $budgetProgram, $programId, $budgetsCascadingId, $budgetStartDate, $budgetEndDate)
    {
        $existingBudget = BudgetCascading::where('id', $budgetsCascadingId)
            ->where('program_id', $programId)
            ->where('budget_start_date', $budgetStartDate)
            ->where('budget_end_date', $budgetEndDate)
            ->first();

        if ($existingBudget) {
            $amount = $existingBudget->budget_amount;
            $updated_amount = $budgetProgram->remaining_amount + $amount;
            BudgetCascading::where('id', $budgetsCascadingId)
                ->where('program_id', $programId)
                ->where('budget_start_date', $budgetStartDate)
                ->where('budget_end_date', $budgetEndDate)
                ->delete();
            $budgetProgram->remaining_amount = $updated_amount;
            $budgetProgram->save();
        }
    }

    public function getBudgetCascading(BudgetProgram $budgetProgram)
    {
        $budgetCascadingData = BudgetCascading::with([
            'program' => function ($query) {
                $query->select('id', 'name');
            }
        ])
            ->where('budget_program_id', $budgetProgram->id)
            ->get();
        return $budgetCascadingData;
    }

    public function getCurrentBudget(Organization $organization, Program $program)
    {
        $currentMonth = Carbon::now()->startOfMonth()->toDateString();
        $currentYear = Carbon::now()->year;
        $currentBudgetCascading = BudgetCascading::where('program_id', $program->id)
            ->where(function ($query) use ($currentYear, $currentMonth) {
                $query->where('budget_start_date', $currentMonth)
                    ->orWhereRaw('YEAR(budget_start_date) = ?', [$currentYear]);
            })
            ->with('budgetCascadingApprovals')
            ->get();
        $result = [];

        if (!$currentBudgetCascading->isEmpty()) {
            $budgetCascadingProgramData = [
                'monthly_budget_amount' => 0,
                'monthly_award_pending' => 0,
                'monthly_budget_amount_remaining' => 0,
                'monthly_awarded_distributed' => 0,
                'yearly_budget_amount' => 0,
                'yearly_award_pending' => 0,
                'yerly_budget_amount_remaining' => 0,
                'yearly_awarded_distributed' => 0,
            ];
            $monthlyAwardDistributedAmount = 0;
            $yearlyAwardDistributedAmount = 0;
            $awardPending = 0;
            $awardSchedule = 0;

            foreach ($currentBudgetCascading as $key => $budgetCascading) {
                $approvalMonth = $budgetCascading->budget_start_date;
                $scheduled_date = $budgetCascading->scheduled_date;
                if ($approvalMonth == $currentMonth) {
                    $budgetCascadingProgramData['monthly_budget_amount'] += $budgetCascading->budget_amount;
                    $budgetCascadingProgramData['monthly_budget_amount_remaining'] += $budgetCascading->budget_amount_remaining;
                }
                $budgetCascadingProgramData['yearly_budget_amount'] += $budgetCascading->budget_amount;
                $budgetCascadingProgramData['yerly_budget_amount_remaining'] += $budgetCascading->budget_amount_remaining;
                foreach ($budgetCascading->budgetCascadingApprovals as $budgetCascadingApproval) {
                    if ($budgetCascadingApproval->approved == 0 && $currentMonth) {
                        $budgetCascadingProgramData['monthly_award_pending'] += $budgetCascadingApproval->amount;
                    }
                    if ($budgetCascadingApproval->approved == 0 && $currentYear) {
                        $budgetCascadingProgramData['yearly_award_pending'] += $budgetCascadingApproval->amount;
                        $awardPending += $budgetCascadingApproval->amount;
                    }

                    if ($budgetCascadingApproval->approved == 1 && $approvalMonth == $currentMonth) {
                        $monthlyAwardDistributedAmount += $budgetCascadingApproval->amount;
                    }
                    if ($budgetCascadingApproval->approved == 1) {
                        $yearlyAwardDistributedAmount += $budgetCascadingApproval->amount;
                    }
                    if ($budgetCascadingApproval->approved == 1) {
                        $awardSchedule += $budgetCascadingApproval->amount;
                    }
                }
            }

            $budgetCascadingProgramData['monthly_awarded_distributed'] += $monthlyAwardDistributedAmount;
            $budgetCascadingProgramData['yearly_awarded_distributed'] += $yearlyAwardDistributedAmount;
            $result["cascadingData"] = array($budgetCascadingProgramData);
            $result["award_pendings"] = $awardPending;
            $result["award_schedule"] = $awardSchedule;

            return $result;
        }

        return $result;

    }

    public static function getParticipantCascadings(Program $program, User $user)
    {
        $budgetCascading = BudgetCascadingApproval::where('user_id', $user->id)
            ->where('approved', 0)
            ->get();
        if ($budgetCascading->isEmpty()) {
            // If the user has no budget cascading for approval, return a count of 0
            return [
                'budget_cascading' => null,
                'count' => 0
            ];
        }

        $groupedBudgetCascadings = $budgetCascading->groupBy('id')->map(function ($group) {
            return [
                'budget_cascading' => $group->first(),
                'count' => $group->count(),
            ];
        });
        $totalCount = $budgetCascading->count();
        $result = $groupedBudgetCascadings->values()->map(function ($item) {
            $budget_cascading = $item['budget_cascading'];
            $budget_cascading->count = $item['count'];
            return $budget_cascading;
        });
        return [
            'budget_cascading' => $result,
            'count' => $totalCount
        ];
    }

    public function getManageBudgetTemplateCSVStream(Program $program, BudgetProgram $budgetProgram)
    {
        //templete
    }
}
