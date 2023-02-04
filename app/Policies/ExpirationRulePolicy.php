<?php
namespace App\Policies;

use App\Models\User;
use App\Models\Program;
use App\Models\Organization;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Gate;

class ExpirationRulePolicy
{
    use HandlesAuthorization;
    public function viewAny(User $user)
    {
        return true || $user->can('expiration-rule-list');
        //This action to get GoalPlanType needs to be public?!
    }
}