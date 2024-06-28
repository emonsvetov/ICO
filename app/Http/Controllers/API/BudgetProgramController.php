<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\BudgetType;
use App\Models\Organization;
use App\Models\BudgetProgram;
use App\Models\BudgetCascading;
use App\Services\BudgetProgramService;
use App\Models\BudgetCascadingApproval;
use App\Http\Requests\BudgetProgramRequest;
use App\Http\Requests\BudgetCascadinApprovalRequest;
use App\Http\Requests\BudgetProgramAssignRequest;
use Illuminate\Http\Request;

class BudgetProgramController extends Controller
{

    protected $budgetProgramService;

    public function __construct(BudgetProgramService $budgetProgramService)
    {
        $this->budgetProgramService = $budgetProgramService;
    }

    public function getBudgetTypes()
    {
        $types = BudgetType::budgetTypeList();
        return response($types);
    }

    public function index(Organization $organization, Program $program)
    {
        //return response(BudgetProgram::all());
        $budgetPrograms = BudgetProgram::where('program_id', $program->id)
            ->with('budget_types')
            ->get();
        return response($budgetPrograms);
    }

    public function store(BudgetProgramRequest $budgetProgramRequest, Organization $organization, Program $program)
    {
        $data = $budgetProgramRequest->validated();
        $data = $data + ['program_id' => $program->id];
        try {
            $budgetProgram = $this->budgetProgramService->createBudgetProgram($data);
            return response($budgetProgram);
        } catch (\Exception $e) {
            return response(['errors' => $e->getMessage()], 422);
        }
    }

    public function update(BudgetProgramRequest $budgetProgramRequest, Organization $organization, Program $program, BudgetProgram $budgetProgram)
    {
        $data = $budgetProgramRequest->validated();
        $budgetProgram = $this->budgetProgramService->updateBudgetProgram($budgetProgram, $data);
        return response($budgetProgram);
    }

    public function show(Organization $organization, Program $program, BudgetProgram $budgetProgram)
    {
        $budgetProgram = $this->budgetProgramService->getBudgetProgram($budgetProgram);
        return response($budgetProgram);
    }

    public function close(Organization $organization, Program $program, BudgetProgram $budgetProgram)
    {
        $budgetProgram = $this->budgetProgramService->closeBudget($budgetProgram);
        return response($budgetProgram);
    }

    public function assign(BudgetProgramAssignRequest $budgetProgramAssignRequest, Organization $organization, Program $program, BudgetProgram $budgetProgram)
    {
        $data = $budgetProgramAssignRequest->validated();
        $data = $data + ['parent_program_id' => $program->id];
        $budgetProgram = $this->budgetProgramService->assignBudget($budgetProgram, $data);
        return response($budgetProgram);
    }

    public function getBudgetCascading(Organization $organization, Program $program, BudgetProgram $budgetProgram)
    {
        $budgetProgram = $this->budgetProgramService->getBudgetCascading($budgetProgram, $program);
        return response($budgetProgram);
    }

    public function getBudgetCascadingApproval(Organization $organization, Program $program)
    {
        $cascadingApprovals = BudgetCascadingApproval::where('program_id', $program->id)
            ->with('event')
            ->with('program')
            ->with('requestor')
            ->with('user')
            ->get();
        $cascading = [];
        foreach ($cascadingApprovals as $key => $cascadingApproval) {
            $cascading[$key]['cascading_id'] = $cascadingApproval['id'];
            $cascading[$key]['program_name'] = $cascadingApproval['program']['name'];
            $cascading[$key]['requested_by'] = $cascadingApproval['requestor']['first_name'] . ' ' . $cascadingApproval['requestor']['last_name'];
            $cascading[$key]['recipient'] = $cascadingApproval['user']['first_name'] . ' ' . $cascadingApproval['user']['last_name'];
            $cascading[$key]['approved_by'] = '';
            $cascading[$key]['event_name'] = $cascadingApproval['event']['name'];
            $cascading[$key]['amount'] = $cascadingApproval['amount'];
            $cascading[$key]['scheduled_date'] = $cascadingApproval['scheduled_date'];
            $cascading[$key]['budgets_available'] = '';
            $cascading[$key]['created_date'] = $cascadingApproval['created_at'];
        }
        if ($cascading) {
            return response($cascading);
        }
        return response([]);
    }

    public function acceptRejectBudgetCascadingApproval(BudgetCascadinApprovalRequest $approvalRequest, Organization $organization, Program $program, BudgetCascadingApproval $budgetCascadinApproval)
    {
        $approver = auth()->user();
        $data = $approvalRequest->validated();
        $budgetCascadingApprovals = BudgetCascadingApproval::whereIn('id', $data['budget_cascading_approval_id'])->get();
        // Update the approved status
        BudgetCascadingApproval::whereIn('id', $data['budget_cascading_approval_id'])
            ->update(['approved' => $data['approved'], 'action_by' => $approver->id]);

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
        return response()->json(['message' => 'Approval status updated successfully.']);
    }

    public function revokeBudgetCascadingApproval(BudgetCascadinApprovalRequest $approvalRequest, Organization $organization, Program $program, BudgetCascadingApproval $budgetCascadingApproval)
    {
        $data = $approvalRequest->validated();
        $ids = $data['budget_cascading_approval_id'];

        if (is_array($ids) && !empty($ids)) {
            $approvals = BudgetCascadingApproval::whereIn('id', $ids)->get();
            foreach ($approvals as $approval) {
                $updatedAmount = $approval->budget_cascading->budget_amount_remaining + $approval->amount;
                BudgetCascading::where('id', $approval->budgets_cascading_id)
                    ->update(['budget_amount_remaining' => $updatedAmount]);
            }

            BudgetCascadingApproval::whereIn('id', $ids)->delete();
        }

        return response()->json(['message' => 'Revoked successfully.']);
    }


    public function getCurrentBudget(Organization $organization, Program $program)
    {
        $currentBudget = $this->budgetProgramService->getCurrentBudget($organization, $program);
        return response($currentBudget);
    }

    public function getPendingCascadingApproval(Organization $organization, Program $program, BudgetCascadingApproval $budgetCascadingApproval, Request $request, $participant)
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
            return response($cascading);
        }
        return response([]);
    }

    public function awardsPending(Organization $organization, Program $program, BudgetCascadingApproval $budgetCascadingApproval)
    {
        $pendingCount = BudgetCascadingApproval::where('program_id', $program->id)
            ->where('approved', 0)
            ->count();

        return response()->json([
            'pending_count' => $pendingCount
        ], 200);
    }


    public function downloadAssignBudgetTemplate(Organization $organization, Program $program, BudgetProgram $budgetProgram)
    {
        //return response()->stream(...($this->budgetProgramService->getManageBudgetTemplateCSVStream($program, $budgetProgram)));
    }
}
