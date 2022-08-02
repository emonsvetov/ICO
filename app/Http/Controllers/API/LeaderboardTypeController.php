<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\LeaderboardType;
use App\Models\Organization;
use App\Models\Program;

class LeaderboardTypeController extends Controller
{
    public function index(Organization $organization, Program $program)
    {
        $leaderboardTypes = LeaderboardType::get();
        if ( $leaderboardTypes->isNotEmpty() )
        {
            return response( $leaderboardTypes );
        }
        return response( [] );
    }
}
