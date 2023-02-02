<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Gate;

class GoalPlanTypePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return true || $user->can('goal-plan-type-list');
        //This action to get GoalPlanType needs to be public?!
    }
}
