<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\UserRequest;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Models\Organization;
use App\Models\User;
use App\Models\Program;
Use Exception;
use DB;

class ProgramUserController extends Controller
{
    public function index( Organization $organization, Program $program )
    {

        if( !$program->users->isNotEmpty() ) return response( [] );

        $keyword = request()->get('keyword');
        $sortby = request()->get('sortby', 'id');
        $direction = request()->get('direction', 'asc');

        $userIds = [];
        $where = [];

        foreach($program->users as $user)    {
            $userIds[] = $user->id;
        }

        $query = User::whereIn('id', $userIds)
                    ->where($where);

        if( $sortby == 'name' )
        {
            $orderByRaw = "first_name $direction, last_name $direction";
        }
        else
        {
            $orderByRaw = "$sortby $direction";
        }

        if( $keyword )
        {
            $query = $query->where(function($query1) use($keyword) {
                $query1->orWhere('id', 'LIKE', "%{$keyword}%")
                ->orWhere(DB::raw("CONCAT(first_name, ' ', last_name)"), 'LIKE', "%{$keyword}%");
            });
        }

        $query = $query->orderByRaw($orderByRaw);

        if ( request()->has('minimal') )
        {
            $users = $query->select('id', 'name')->get();
        }
        else {
            $users = $query->with(['roles' => function ($query) use($program) {
                $query->wherePivot('program_id', '=', $program->id);
            }])->paginate(request()->get('limit', 20));
        }

        if ( $users->isNotEmpty() )
        {
            return response( $users );
        }

        return response( [] );
    }

    public function store( UserRequest $request, Organization $organization, Program $program )
    {

        $validated = $request->validated();

        $validated['organization_id'] = $organization->id;
        $user = User::createAccount( $validated );

        if( $user ) {
            $program->users()->sync( [ $user->id ], false );
            if( isset($validated['roles']) ) {
                $user->syncProgramRoles($program->id, $validated['roles']);
            }
        }

        return response([ 'user' => $user ]);
    }

    public function show( Organization $organization, Program $program, User $user ): UserResource
    {
        return $this->UserResponse($user);
    }

    public function update( UserRequest $request, Organization $organization, Program $program, User $user)
    {

        $validated = $request->validated();
        $user->update( $validated );

        if( !empty($validated['roles']) )   {
            $user->syncProgramRoles($program->id, $validated['roles']);
        }

        return response([ 'user' => $user ]);
    }

    public function delete(Organization $organization, Program $program, User $user )
    {

        try{
            $program->users()->detach( $user );
        }   catch( Exception $e) {
            return response(['errors' => 'User removal failed', 'e' => $e->getMessage()], 422);
        }

        return response([ 'success' => true ]);
    }


    public function readBalance(Organization $organization, Program $program, User $user )
    {
        $amount_balance = $user->readAvailableBalance( $program, $user);
        $factor_valuation = $program->factor_valuation;
        $points_balance = $amount_balance * $program->factor_valuation;
        return response([
            'points' => $points_balance,
            'amount' => $amount_balance,
            'factor' => $factor_valuation
        ]);
    }

    protected function userResponse(User $user): UserResource
    {
        return new UserResource($user->load('roles'));
    }
}
