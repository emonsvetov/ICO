<?php

namespace App\Repositories;

use App\Models\User;
use App\Repositories\interfaces\UserRepositoryInterface;

class UserRepository implements UserRepositoryInterface
{

    /**
     * @inheritDoc
     */
    public function getParticipantPrograms(User $user): array
    {
        $resultPrograms = [];
        $programs = $user->programs()->get();
        foreach ($programs as $program){
            if ($user->isProgramParticipant($program)){
                $resultPrograms[] = $program;
            }
        }
        return $resultPrograms;
    }

}
