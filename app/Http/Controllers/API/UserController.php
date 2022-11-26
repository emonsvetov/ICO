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
        if ( !$organization )
        {
            return response(['errors' => 'Invalid Organization'], 422);
        }

        $sortby = request()->get('sortby', 'id');
        $keyword = request()->get('keyword');
        $direction = request()->get('direction', 'asc');
        $limit = request()->get('limit', 10);

        $where = [];

        // if( $keyword)
        // {
        //     $where[] = [ DB::raw("CONCAT(first_name, ' ', last_name)"), 'LIKE', "%{$keyword}%" ];

        //     //more search criteria here
        // }
        // return $organization;
        $query = User::where($where)->withOrganization($organization);

        if( $keyword )
        {
            $query = $query->where(function($query1) use($keyword) {
                $query1->orWhere('id', 'LIKE', "%{$keyword}%")
                ->orWhere('email', 'LIKE', "%{$keyword}%")
                ->orWhere(DB::raw("CONCAT(first_name, ' ', last_name)"), 'LIKE', "%{$keyword}%");
            });
        }

        if( $sortby == 'name' )
        {
            $orderByRaw = "first_name $direction, last_name $direction";
        }
        else
        {
            $orderByRaw = "$sortby $direction";
        }

        $query = $query->orderByRaw($orderByRaw);

        if ( request()->has('minimal') )
        {
            $users = $query->select('id', 'first_name', 'last_name')->with(['roles'])->get();
        } else {
            $users = $query->with(['roles'])->paginate(request()->get('limit', 10));
        }

        if ( $users->isNotEmpty() )
        {
            return response( $users );
        }

        return response( [] );
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
        $user->programRoles = $user->getProgramsRoles();
        return new UserResource($user);
    }
}
