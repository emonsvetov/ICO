<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\UserStatusRequest;
use App\Http\Controllers\Controller;
use App\Services\UserService;
use App\Models\Organization;
use App\Models\User;
Use Exception;

class UserStatusController extends Controller
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index( Organization $organization )
    {
        return response( $this->userService->listStatus() );
    }

    public function update(UserStatusRequest $request, Organization $organization, User $user )
    {
        $updated = $this->userService->updateStatus($request->validated(), $user);
        return response(['updated' => $updated]);
    }
}
