<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Gate;

class EventTypePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return true || $user->can('eventtype-list');
        //This action to get EventTypes needs to be public?!
    }
}
