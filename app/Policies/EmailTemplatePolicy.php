<?php

namespace App\Policies;

use App\Models\User;
use Illuminate\Auth\Access\HandlesAuthorization;
use Illuminate\Support\Facades\Gate;

class EmailTemplatePolicy
{
    use HandlesAuthorization;

    public function viewAny(User $user)
    {
        return $user->can('emailtemplate-list');
    }
}
