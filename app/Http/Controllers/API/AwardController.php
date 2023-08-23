<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\ReclaimPeerPointsRequest;
use App\Http\Requests\AwardRequest;
use App\Models\Organization;
use App\Models\Program;
use App\Models\User;
use App\Services\AwardService;

class AwardController extends Controller
{
    public function store(
        AwardRequest $request,
        Organization $organization,
        Program $program,
        AwardService $awardService
    ) {
        try {
            /** @var User $awarder */
            $awarder = auth()->user();
            $newAward = $awardService->create($program, $organization, $awarder, $request->validated());
            return response($newAward);
        } catch (\Exception $e) {
            return response(['errors' => 'Award creation failed', 'e' => $e->getMessage()], 422);
        }
    }

    public function readListReclaimablePeerPoints(
        Organization $organization,
        Program $program,
        User $user,
        AwardService $awardService
    ) {
        $limit=9999999;
        $offset=0;
        return response($awardService->readListReclaimablePeerPointsByProgramAndUser(
            $program,
            $user,
            $limit,
            $offset
        ));
    }
    public function ReclaimPeerPoints(
        ReclaimPeerPointsRequest $request,
        Organization $organization,
        Program $program,
        User $user,
        AwardService $awardService
    ) {
        try{
            $response = $awardService->reclaimPeerPoints($program, $user, $request->validated()['reclaim']);
            return response($response);
        } catch (\Exception $e) {
            return response(['errors' => 'Reclaim failed', 'e' => $e->getMessage()], 422);
        }
    }
}
