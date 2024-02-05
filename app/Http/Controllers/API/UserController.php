<?php

namespace App\Http\Controllers\API;

use App\Models\Program;
use App\Models\Role;
use App\Services\reports\ReportServiceAbstract;
use App\Services\reports\User\ReportServiceUserChangeLogs;
use App\Services\reports\User\ReportServiceUserGiftCodeReedemed;
use App\Services\reports\User\ReportServiceUserHistory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use App\Http\Resources\UserResource;
use App\Http\Requests\UserRequest;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

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

    protected function history(Request $request, Organization $organization, User $user) {
        $params = [
            'program_account_holder_id' => NULL,
            'user_account_holder_id' => $user->account_holder_id,
            'paginate' => true,
            'limit' => $request->get('limit'),
            'offset' => ($request->get('page') - 1) * $request->get('limit'),
            'order' => $request->get('sortby'),
            'dir' => $request->get('direction'),
            'programs' => $request->get('programs'),
        ];
        $params = array_merge($params, $request->all());
        $report = new ReportServiceUserHistory($params);

        return response($report->getReport());
    }

    protected function giftCodesRedeemed(Request $request, Organization $organization, User $user) {
        $params = [
            'programId' => NULL,
            'user_id' => $user->id,
            'paginate' => true,
            'limit' => $request->get('limit'),
            'offset' => ($request->get('page') - 1) * $request->get('limit'),
            'order' => $request->get('sortby'),
            'dir' => $request->get('direction'),
        ];
        $params = array_merge($params, $request->all());
        $report = new ReportServiceUserGiftCodeReedemed($params);

        return response($report->getReport());
    }

    public function changeLogs(Request $request, Organization $organization, Program $program, User $user)
    {
        $params = [
            'programId' => NULL,
            'user_id' => $user->id,
            'paginate' => true,
            'limit' => $request->get('limit'),
            'offset' => ($request->get('page') - 1) * $request->get('limit'),
        ];
        $params = array_merge($params, $request->all());
        $report = new ReportServiceUserChangeLogs($params);

        return response($report->getReport());
    }

    public function reclaimItems(Request $request, $organization, User $user, $program,UserService $service)
    {
        $res = $service->reclaimPointItems($user->account_holder_id, $program);
        return response(['data'=>$res]);
    }

    public function reclaim(Request $request, UserService $service)
    {
        $res = $service->reclaim($request);
        return response($res);
    }
}
