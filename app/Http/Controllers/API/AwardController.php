<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\AwardRequest;
use App\Models\Organization;
use App\Models\Program;
use App\Models\Award;
use App\Models\User;
use App\Services\AwardService;
use Exception;

class AwardController extends Controller
{
    public function store(
        AwardRequest $request,
        Organization $organization,
        Program $program,
        AwardService $awardService
    ) {
        try {
            /** @var User $currentUser */
            $awarder = auth()->user();
            $newAward = $awardService->create($program, $organization, $awarder, $request->validated());
            return response($newAward);
        } catch (\Exception $e) {
            return response(['errors' => 'Award creation failed', 'e' => $e->getMessage()], 422);
        }
    }

}
