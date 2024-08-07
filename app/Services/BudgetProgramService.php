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
use App\Models\Traits\Filterable;
use App\Models\Traits\UserFilters;
use Illuminate\Http\Request;

class BudgetProgramService
{
    use Filterable, UserFilters;

    const ASSIGN_BUDGET_CSV_FROM_HEADER = ["Total Budget", "Remaining Budget", "Budget Type", "Budget Start Date", "Budget End Date"];
    const ASSIGN_BUDGET_CSV_TO_HEADER = ["Assign Budget to Program Id", "Assign Budget to program Name"];
    const MONTHLY=1;
    const MONTHLY_ROLLOVER=2;
    const SPECIFIED_PERIOD=3;
    const YEARLY=4;

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
            if ($data['budget_amount'] <= 0) {
                throw new Exception('Budget Amount should be grater than 0');
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

        if ($data['budget_type'] == self::MONTHLY) {
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

    public function getBudgetCascadingApproval(Program $program, $title, Request $request)
    {
        if ($title) {
            $p_program = new Program();
            $program_id = $p_program->get_top_level_program_id($program->id);
            $approved = $title == 'manage-approvals' ? 1 : ($title == "cascading-approvals" ? 0 : response([]));
            self::$query = BudgetCascadingApproval::where('program_id', $program_id)
                ->where('approved', $approved)
                ->with('event')
                ->with('program')
                ->with('requestor')
                ->with('approved_by')
                ->with('user');

            // Get the page and limit from the request
            $limit = $request->query('limit');
            $page = $request->query('page');

            // Paginate the results
            $cascadingApprovals = self::$query->paginate($limit, ['*'], 'page', $page);

            $cascading = [];
            foreach ($cascadingApprovals as $key => $cascadingApproval) {
                $approved_by = $cascadingApproval['approved_by']['first_name'] . ' ' . $cascadingApproval['approved_by']['last_name'] ?? '';
                $cascading[$key]['cascading_id'] = $cascadingApproval['id'];
                $cascading[$key]['program_name'] = $cascadingApproval['program']['name'];
                $cascading[$key]['requested_by'] = $cascadingApproval['requestor']['first_name'] . ' ' . $cascadingApproval['requestor']['last_name'];
                $cascading[$key]['recipient'] = $cascadingApproval['user']['first_name'] . ' ' . $cascadingApproval['user']['last_name'];
                $cascading[$key]['approved_by'] = $approved_by;
                $cascading[$key]['event_name'] = $cascadingApproval['event']['name'];
                $cascading[$key]['amount'] = $cascadingApproval['amount'];
                $cascading[$key]['scheduled_date'] = $cascadingApproval['scheduled_date'];
                $cascading[$key]['budgets_available'] = ''; // You can populate this field if needed
                $cascading[$key]['created_date'] = $cascadingApproval['created_at'];
            }

            if ($cascadingApprovals->count() > 0) {
                return [
                    'current_page' => $cascadingApprovals->currentPage(),
                    'data' => $cascading,
                    'first_page_url' => $cascadingApprovals->url(1),
                    'from' => $cascadingApprovals->firstItem(),
                    'last_page' => $cascadingApprovals->lastPage(),
                    'last_page_url' => $cascadingApprovals->url($cascadingApprovals->lastPage()),
                    'links' => [
                        [
                            'url' => $cascadingApprovals->previousPageUrl(),
                            'label' => '&laquo; Previous',
                            'active' => false,
                        ],
                        [
                            'url' => $cascadingApprovals->url(1),
                            'label' => '1',
                            'active' => true,
                        ],
                    ],
                    'next_page_url' => $cascadingApprovals->nextPageUrl(),
                    'path' => $cascadingApprovals->path(),
                    'per_page' => $cascadingApprovals->perPage(),
                    'prev_page_url' => $cascadingApprovals->previousPageUrl(),
                    'to' => $cascadingApprovals->lastItem(),
                    'total' => $cascadingApprovals->total(),
                ];
            }
            return $cascadingApprovals;
        }
    }

    public function acceptRejectBudgetCascadingApproval($data)
    {
        $approver = auth()->user();
        $budgetCascadingApprovals = BudgetCascadingApproval::whereIn('id', $data['budget_cascading_approval_id'])->get();
        // Update the approved status
        BudgetCascadingApproval::whereIn('id', $data['budget_cascading_approval_id'])
            ->update(['approved' => $data['approved'], 'rejection_note' => $data['rejection_note'], 'action_by' => $approver->id]);

        if ($data['approved'] == '1') {
            // Additional steps when the approval is accepted
            foreach ($budgetCascadingApprovals as $approval) {
                $budgetCascading = BudgetCascading::find($approval->budgets_cascading_id);
                if ($budgetCascading) {
                    $budgetCascading->budget_amount_remaining -= $approval->amount;
                    $budgetCascading->save();
                }
            }
        }
        return ['message' => 'Approval status updated successfully.'];
    }

    public function revokeBudgetCascadingApproval($data)
    {
        $ids = $data['budget_cascading_approval_id'];

        if (is_array($ids) && !empty($ids)) {
            $approvals = BudgetCascadingApproval::whereIn('id', $ids)->get();
            foreach ($approvals as $approval) {
                $updatedAmount = $approval->budget_cascading->budget_amount_remaining + $approval->amount;

                BudgetCascading::where('id', $approval->budgets_cascading_id)
                    ->update(['budget_amount_remaining' => $updatedAmount]);
            }

            return BudgetCascadingApproval::whereIn('id', $ids)->delete();
        }

        return false;
    }

    public function getPendingCascadingApproval($participant)
    {
        $budgetCascadingPendingData = BudgetCascadingApproval::where('user_id', $participant)
            ->where('approved', 0)
            ->with('event')
            ->with('requestor')
            ->get();
        $cascading = [];
        foreach ($budgetCascadingPendingData as $key => $cascadingApproval) {
            $cascading[$key]['id'] = $cascadingApproval['id'];
            $cascading[$key]['event_name'] = $cascadingApproval['event']['name'];
            $cascading[$key]['amount'] = $cascadingApproval['amount'];
            $cascading[$key]['created_date'] = $cascadingApproval['created_at'];
            $cascading[$key]['submitted_by'] = $cascadingApproval['requestor']['first_name'] . ' ' . $cascadingApproval['requestor']['last_name'];
            $cascading[$key]['date_of_award_submission'] = $cascadingApproval['scheduled_date'];
        }
        if ($cascading) {
            return $cascading;
        }
        return [];
    }

    public function awardsPending(Program $program)
    {
        $pendingCount = BudgetCascadingApproval::where('parent_id', $program->id)
            ->where('approved', 0)
            ->count();

        return [
            'pending_count' => $pendingCount
        ];
    }

    public function manageScheduleDate($data)
    {
        // Update the schedule Date
        $approver = auth()->user();
        BudgetCascadingApproval::whereIn('id', $data['budget_cascading_approval_id'])
            ->update(['scheduled_date' => $data['scheduled_date'], 'action_by' => $approver->id]);
        return ['message' => 'Scheduled Date updated successfully.'];
    }

    public function getManageBudgetTemplateCSVStream(Program $program, BudgetProgram $budgetProgram)
    {
        //todo
    }
}
