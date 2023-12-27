<?php

namespace App\Services;

use App\Models\Merchant;

class MerchantService
{

    private UserService $userService;

    public function __construct(
        UserService $userService
    ) {
        $this->userService = $userService;
    }


}
