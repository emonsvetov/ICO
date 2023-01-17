<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\AwardService;
use App\Models\Organization;
use App\Models\Program;
use App\Models\User;

class ParticipantController extends Controller
{
    public function myPoints( 
        Organization $organization, 
        Program $program,
        User $user,  
        AwardService $awardService)
    {
        $listExpireFuture = $awardService->readListExpireFuture($program, $user);
        return response($listExpireFuture);
    }
}
