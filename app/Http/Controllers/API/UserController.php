<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\DB;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use App\Http\Resources\UserResource;
use App\Http\Requests\UserRequest;
use App\Models\Organization;
use App\Models\User;

class UserController extends Controller
{
    private UserService $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function index( Organization $organization )
    {
        return response($this->userService->getIndexData( $organization ));
    }

    public function store(UserRequest $request, Organization $organization)
    {
        try {
            $validated = $request->validated();
            $validated['organization_id'] = $organization->id;
            $user = User::createAccount( $validated );
            if( !empty($validated['roles']))   {
                $user->syncRoles( [$validated['roles']] );
            }
            return response([ 'user' => $user ]);
        } catch (\Exception $e )    {
            return response(['errors' => $e->getMessage()], 422);
        }
    }

    public function show( Organization $organization, User $user ): UserResource
    {
        return $this->UserResponse($user);
    }

    public function update(UserRequest $request, Organization $organization, User $user )
    {
        $newUser = $this->userService->update($request, $user);

        return response(['user' => $newUser]);
    }

    protected function UserResponse(User $user): UserResource
    {
        $user->load('roles');
        $user->programRoles = $user->compileProgramRoles($user->getAllProgramRoles());
        return new UserResource($user);
    }
}
