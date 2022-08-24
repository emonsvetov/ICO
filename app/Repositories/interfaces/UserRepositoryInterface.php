<?php

namespace App\Repositories\interfaces;

use App\Models\User;

interface UserRepositoryInterface
{

    /**
     * @param User $user
     * @return array
     */
    public function getParticipantPrograms(User $user): array;

}
