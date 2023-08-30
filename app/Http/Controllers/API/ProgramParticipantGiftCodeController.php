<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\MediumInfo;
use App\Services\AccountService;
use App\Services\AwardService;
use App\Models\Organization;
use App\Models\Program;
use App\Models\User;

class ProgramParticipantGiftCodeController extends Controller
{
    public function index(Organization $organization, Program $program, User $user)
    {
        $limit = request()->get('pageSize', 30);
        $page = request()->get('page', 1);
        $offset = ($page - 1) * $limit;
        $giftCodes = MediumInfo::getListRedeemedByParticipant($user->id, false, $limit, $offset);

        if ($giftCodes->isNotEmpty()) {
            return response($giftCodes);
        }
        return response([]);
    }
}
