<?php

namespace App\Http\Controllers\API;

use App\Models\AccountType;
use App\Models\Role;
use App\Services\reports\User\ReportServiceUserHistory;
use App\Services\reports\User\ReportServiceUserGiftCodeReedemed;
use App\Services\reports\User\ReportServiceUserChangeLogs;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Exception;

use App\Http\Requests\ProgramUserAssignRoleRequest;
use App\Services\Program\ProgramUserService;
use App\Http\Controllers\Controller;
use App\Http\Resources\UserResource;
use App\Http\Requests\UserRequest;
use App\Services\AccountService;
use App\Services\AwardService;
use App\Services\UserService;
use App\Models\Organization;
use App\Models\Program;
use App\Models\User;
use Illuminate\Support\Facades\Validator;

class ProgramUserController extends Controller
{
    public function index(Organization $organization, Program $program)
    {

        if ( ! $program->users->isNotEmpty()) {
            return response([]);
        }

        $keyword = request()->get('keyword');
        $sortby = request()->get('sortby', 'id');
        $direction = request()->get('direction', 'asc');

        $userIds = [];
        $where = ['organization_id' => $organization->id];

        foreach ($program->users as $user) {
            $userIds[] = $user->id;
        }

        $query = User::whereIn('id', $userIds)
            ->where($where);

        if ($sortby == 'name') {
            $orderByRaw = "first_name $direction, last_name $direction";
        } else {
            $orderByRaw = "$sortby $direction";
        }

        if ($keyword) {
            $query = $query->where(function ($query1) use ($keyword) {
                $query1->orWhere('id', 'LIKE', "%{$keyword}%")
                    ->orWhere('email', 'LIKE', "%{$keyword}%")
                    ->orWhere(DB::raw("CONCAT(first_name, ' ', last_name)"), 'LIKE', "%{$keyword}%")
                    ->orWhere(DB::raw("CONCAT(last_name, ' ', first_name)"), 'LIKE', "%{$keyword}%");
            });
        }

        $query = $query->orderByRaw($orderByRaw);

        if (request()->has('minimal')) {
            $users = $query->select('id', 'name')->get();
        } else {
            $users = $query->with([
                'roles' => function ($query) use ($program) {
                    $query->wherePivot('program_id', '=', $program->id);
                },
                'status'
            ])->paginate(request()->get('limit', 20));
        }

        if ($users->isNotEmpty()) {
            return response($users);
        }

        return response([]);
    }

    public function store(UserRequest $request, ProgramUserService $programUserService, Organization $organization, Program $program)
    {
        DB::beginTransaction();
        try{
            $validated = $request->validated();
            $user = $programUserService->create($program, $validated);
            DB::commit();
            return response(['user' => $user]);
        } catch (\Exception $e )    {
            DB::rollBack();
            $error = 'Error adding user to program program: %s';
            return response(['errors' => $error], 422);
        }
    }

    public function show(Organization $organization, Program $program, User $user): UserResource
    {
        $include_balance = request('include_balance', false);
        if( $include_balance )   {
            $user = (new \App\Services\Program\ProgramUserService)->attachBalanceToUser($user, $program);
        }
        return $this->UserResponse($user);
    }

    public function history(Request $request, Organization $organization, Program $program, User $user)
    {
        $params = [
            'program_account_holder_id' => $program->account_holder_id,
            'user_account_holder_id' => $user->account_holder_id,
            'paginate' => true,
            'limit' => $request->get('limit'),
            'offset' => ($request->get('page') - 1) * $request->get('limit'),
            'order' => $request->get('sortby'),
            'dir' => $request->get('direction'),
        ];
        $params = array_merge($params, $request->all());
        $report = new ReportServiceUserHistory($params);

        return response($report->getReport());
    }

    public function giftCodesRedeemed(Request $request, Organization $organization, Program $program, User $user)
    {
        $params = [
            'programId' => $program->id,
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
            'programId' => $program->id,
            'user_account_holder_id' => $user->account_holder_id,
            'paginate' => true,
            'limit' => $request->get('limit'),
            'offset' => ($request->get('page') - 1) * $request->get('limit'),
        ];
        $params = array_merge($params, $request->all());
        $report = new ReportServiceUserChangeLogs($params);

        return response($report->getReport());
    }

    public function update(UserRequest $request, Organization $organization, Program $program, User $user)
    {
        $validated = $request->validated();
        $user->update($validated);

        if ( ! empty($validated['roles'])) {
            $user->syncProgramRoles($program->id, $validated['roles']);
        }

        if (!empty($validated['award_level'])) {
            $user->syncAwardLevelsHasUsers($program->id, $validated['award_level']);
        }

        return response(['user' => $user]);
    }

    public function delete(Organization $organization, Program $program, User $user)
    {

        try {
            $program->users()->detach($user);
        } catch (Exception $e) {
            return response(['errors' => 'User removal failed', 'e' => $e->getMessage()], 422);
        }

        return response(['success' => true]);
    }

    public function readBalance(
        Organization $organization,
        Program $program,
        User $user,
        UserService $userService,
        AccountService $accountService
    ) {
        $pointsEarned = $accountService->read_awarded_total_for_participant($program, $user);
        $factor_valuation = $program->factor_valuation;
        $peerBalance = $userService->readAvailablePeerBalance($user, $program);
        $amount_balance = $user->readAvailableBalance($program, $user) + $peerBalance;
        $pointBalance = $amount_balance * $program->factor_valuation;
        $expiredBalance = $accountService->readExpiredBalance($user, $program);
        $redeemedBalance = $accountService->readRedeemedBalance($user, $program);
        return response([
            'pointBalance' => $pointBalance,
            'points' => $pointsEarned,
            'amount' => $amount_balance,
            'factor' => $factor_valuation,
            'peerBalance' => 0, //todo
            'redeemedBalance' => $redeemedBalance,
            'expiredBalance' => $expiredBalance,
        ]);
    }

    public function readEventHistory(
        Organization $organization,
        Program $program,
        User $user,
        AwardService $awardService
    ) {
        $limit = request()->get('pageSize', 10);
        $page = request()->get('page', 1);
        $offset = ($page - 1) * $limit;

        return response($awardService->readEventHistoryByParticipant(
            $user->account_holder_id,
            $limit,
            $offset
        ));
    }

    protected function userResponse(User $user): UserResource
    {
        return new UserResource($user->load('roles'));
    }

    public function userToAssign(Organization $organization, Program $program)
    {
        $keyword = request()->get('keyword');
        $sortby = request()->get('sortby', 'id');
        $direction = request()->get('direction', 'asc');

        $where = ['organization_id' => $program->organization_id]; //Only users from same organization can be assigned to a program

        $query = User::where($where);

        if ($sortby == 'name') {
            $orderByRaw = "first_name $direction, last_name $direction";
        } else {
            $orderByRaw = "$sortby $direction";
        }

        if ($keyword) {
            $query = $query->where(function ($query1) use ($keyword) {
                $query1->orWhere('id', 'LIKE', "%{$keyword}%")
                    ->orWhere('email', 'LIKE', "%{$keyword}%")
                    ->orWhere(DB::raw("CONCAT(first_name, ' ', last_name)"), 'LIKE', "%{$keyword}%")
                    ->orWhere(DB::raw("CONCAT(last_name, ' ', first_name)"), 'LIKE', "%{$keyword}%");
            });
        }

        $query = $query->orderByRaw($orderByRaw);

        $users = $query->with([
            'roles' => function ($query) use ($program) {
                $query->wherePivot('program_id', '=', $program->id);
            },
            'status'
        ])->paginate(request()->get('limit', 1000));

        if ($users->isNotEmpty()) {
            return response($users);
        }

        return response([]);
    }

    public function assignRole(
        ProgramUserAssignRoleRequest $request,
        Organization $organization,
        Program $program,
        User $user
    ) {
        $validated = $request->validated();

        DB::beginTransaction();

        try {
            $program->users()->sync([$user->id], false);
            $user->syncProgramRoles($program->id, $validated['roles']);
            DB::commit();
            return response(['success' => true]);
        } catch (Exception $e) {
            DB::rollBack();
            return response(['errors' => 'Program User Role assignment failed', 'e' => $e->getMessage()], 422);
        }
    }

    public function storeRaw(UserRequest $request, ProgramUserService $programUserService)
    {
        DB::beginTransaction();
        try{
            $program = Program::where('id', $request->get('program_id'))->first();
            $validated = $request->validated();
            if( !empty($validated['role']))
            {
                $roles[] = Role::getIdByName($validated['role']);
                $validated['roles'] = $roles;
            }
            $user = $programUserService->create($program, $validated);
            DB::commit();
            return response(['user' => $user]);
        } catch (\Exception $e )    {
            DB::rollBack();
            return response(['errors' => $e->getMessage()], 422);
        }
    }

    public function updateRaw(Request $request)
    {
        DB::beginTransaction();
        try {
            $user = $request->get('external_id') ? User::where('external_id', $request->get('external_id'))->first()
                : User::where('email', $request->get('email'))->first();
            $program = Program::where('id', $request->get('program_id'))->first();
            $userRequest = UserRequest::createFrom($request);
            $userRequest->user = $user;
            $validator = Validator::make($userRequest->all(), $userRequest->rules());
            $userRequest->setValidator($validator);
            $validated = $userRequest->validated();
            if (!empty($validated['role'])) {
                $roles[] = Role::getIdByName($validated['role']);
                $validated['roles'] = $roles;
            }
            $user->update($validated);
            if (!empty($validated['roles'])) {
                $user->syncProgramRoles($program->id, $validated['roles']);
            }

            DB::commit();
            return response(['user' => $user]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response(['errors' => $e->getMessage()], 422);
        }
    }

    public function changeStatusRaw(Request $request )
    {
        DB::beginTransaction();
        try {
            $user = $request->get('external_id') ? User::where('external_id', $request->get('external_id'))->first()
                : User::where('email', $request->get('email'))->first();
            $status = User::getStatusByName($request->get('status'));
            if( !$status->exists() ) {
                throw new \Exception('Status does not exists');
            }
            $user->update(['user_status_id' => $status->id]);
            DB::commit();
            return response(['user' => $user]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response(['errors' => $e->getMessage()], 422);
        }
    }
}
