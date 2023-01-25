<?php

namespace App\Models;

use App\Models\BaseModel;

class LeaderboardType extends BaseModel
{
    const LEADERBOARD_TYPE_EVENT_SUMMARY = 'Event Summary';
    const LEADERBOARD_TYPE_EVENT_VOLUME = 'Event Volume';
    const LEADERBOARD_TYPE_GOAL_PROGRESS = 'Goal Progress';

    protected $guarded = [];
}
