<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\ProgramParticipantStatusRequest;
use App\Http\Controllers\Controller;
use App\Models\AwardLevel;
use App\Models\BudgetCascadingApproval;
use App\Services\BudgetProgramService;
use App\Services\ProgramService;
use App\Services\AccountService;
use App\Services\UserService;
use App\Models\Organization;
use App\Models\Program;
use App\Models\User;

class ProgramParticipantController extends Controller
{

    public function index(ProgramService $programService, Organization $organization, Program $program, UserService $userService)
    {
        $users = $programService->getParticipants($program, true);
        $users->load('status');

        foreach ($users as $key => $user) {
            $users[$key]['pointBalance'] = AccountService::readAvailableBalanceForUser($program, $user);
            $users[$key]['redeemedBalance'] = AccountService::readRedeemedTotalForParticipant($program, $user);
            $users[$key]['peerBalance'] = $userService->readAvailablePeerBalance($user, $program);//todo
            $awardLevel = AwardLevel::find($user->award_level);
            $budgetCascadingApprovals = BudgetProgramService::getParticipantCascadings($program, $user);

            if ($awardLevel) {
                $user->award_level = $awardLevel->name;
            } else {
                $user->award_level = '';
            } 
              
            $user->budgetCascadingApproval = $budgetCascadingApprovals;
            $users[$key]['totalPointsRewarded'] = AccountService::readAwardedTotalForUser($program, $user);
        }

        if ($users) {
            return response($users);
        }
        return response([]);
    }

    public function changeStatus(ProgramParticipantStatusRequest $request, Organization $organization, Program $program)
    {
        $validated = $request->validated();
        $userIds = $validated['users'];
        $status = User::getStatusByName($validated['status']);
        if (!$status->exists()) {
            return response(['errors' => 'Status does not exists'], 422);
        }
        $result = User::whereIn('id', $userIds)->update(['user_status_id' => $status->id]);
        return response($result);
    }
}
