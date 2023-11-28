<?php

namespace App\Services;

use App\Http\Requests\UserRequest;
use App\Models\AccountType;
use App\Models\Program;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Traits\Filterable;
use App\Models\Traits\UserFilters;
use App\Models\Status;
use App\Models\User;
use App\Http\Traits\MediaUploadTrait;
use Illuminate\Support\Facades\DB;

class UserService
{
    use Filterable, UserFilters, MediaUploadTrait;

    private AccountService $accountService;

    public function __construct(AccountService $accountService)
    {
        $this->accountService = $accountService;
    }

    public function getIndexData($organization)
    {
        $sortby = request()->get('sortby', 'id');
        $keyword = request()->get('keyword');
        $direction = request()->get('direction', 'asc');
        $status = request()->get('status', '');
        $orgId = request()->get('orgId', '');

        $where = [];

        $query = User::where($where)->withOrganization($organization);

        if( $keyword )
        {
            $query = $query->where(function($query1) use($keyword) {
                $query1->orWhere('id', 'LIKE', "%{$keyword}%")
                ->orWhere('email', 'LIKE', "%{$keyword}%")
                ->orWhere(DB::raw("CONCAT(first_name, ' ', last_name)"), 'LIKE', "%{$keyword}%");
            });
        }

        if( $orgId )
        {
            $orgIds = explode(',', $orgId);
            $query->whereIn('organization_id', $orgIds);
        }

        if ( $status ){
            $statuses = explode(',', $status);
            $statusIds = [];
            foreach ($statuses as $status){
                $statusIds[] = User::getStatusIdByName($status);
            }
            $query->whereIn('user_status_id', $statusIds);
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
            $users = $query->select('id', 'first_name', 'last_name')->with(['roles', 'status'])->get();
        } else {
            $users = $query->with(['roles', 'status'])->paginate(request()->get('limit', 10));
        }

        if ( $users->isNotEmpty() )
        {
            return $users ;
        }

        return [];
    }

    public function getSuperAdmins($paginate = false)
    {
        $query = User::whereHas('roles', function (Builder $query) {
            $query->where('name', 'LIKE', config('roles.super_admin'));
        });
        if ($paginate) {
            return $query->paginate();
        } else {
            return $query->get();
        }
    }

    public function getParticipants($program, $paginate = false)
    {
        $userStatus = User::getStatusByName(User::STATUS_DELETED);
        $program = self::GetModelByMixed($program);
        if ( ! $program->exists()) {
            return;
        }
        // DB::enableQueryLog();
        self::$query = User::whereHas('roles', function (Builder $query) use ($program) {
            $query->where('name', 'LIKE', config('roles.participant'))
                ->where('model_has_roles.program_id', $program->id);
        });
        self::$query->where('user_status_id', '!=', $userStatus->id);
        self::_makeParams();
        self::applyFilters();
        if ($paginate) {
            $users = self::$query->paginate(self::$PARAMS['limit']);
        } else {
            $users = self::$query->get();
        }
        // pr(DB::getQueryLog());
        return $users;
    }

    /**
     * @param UserRequest $request
     * @param User $user
     * @return User|null
     */
    public function update(UserRequest $request, User $user): ?User
    {
        $validated = $request->validated();
        $fieldsToUpdate = array_filter(
            $validated,
            fn($key) => ! in_array($key, $user->getImageFields()),
            ARRAY_FILTER_USE_KEY
        );

        $uploads = $this->handleMediaUpload($request, $user, true);
        if ($uploads) {
            foreach ($uploads as $key => $upload) {
                if (in_array($key, $user->getImageFields())) {
                    $fieldsToUpdate[$key] = $upload;
                }
            }
        }

        $user->update($fieldsToUpdate);

        if ( ! empty($validated['roles'])) {
            $this->updateRoles($user, $validated['roles']);
        }

        return $user;
    }

    /**
     * @param User $user
     * @param array $roles
     * @return void
     */
    public function updateRoles(User $user, array $roles)
    {
        //only a Super admin or a Admin can be assigned here. so we need to keep existing program roles intact
        $newRoles = [];
        $columns = ['program_id' => 0]; //a hack!
        $user->roles()->wherePivot('program_id', '=', 0)->detach();
        foreach ($roles as $role_id) {
            $newRoles[$role_id] = $columns;
        }
        $user->roles()->attach($newRoles);
        // $user->syncRoles( [$validated['roles']] );
    }

    /**
     * @param User $user
     * @param Program $program
     * @return float
     */
    public function readAvailablePeerBalance(User $user, Program $program): float
    {
        $accountTypeName = AccountType::getTypePeer2PeerPoints();

        return $this->accountService->readBalance($user->account_holder_id, $accountTypeName);
    }

    public function listStatus()
    {
        return Status::where('context', 'Users')->get();
    }

    public function updateStatus($validated, $user)
    {
        return $user->update( ['user_status_id' => $validated['user_status_id']] );
    }

    public function getUsersToRemind()
    {
        $userClassForSql = str_replace('\\', '\\\\\\\\', get_class(new User));
        User::$withoutAppends = true;
        $query = User::select(
            'users.id',
            'users.first_name',
            'users.last_name',
            'users.email',
            'users.user_status_id',
            'roles.name AS roleName',
            'model_has_roles.program_id',
        );
        $query->join('model_has_roles', function ($join) use ($userClassForSql) {
            $join->on('model_has_roles.model_id', '=', 'users.id');
            $join->on('model_has_roles.model_type', 'like', DB::raw("'" . $userClassForSql . "'"));
        });
        $query->join('roles', 'model_has_roles.role_id', '=', 'roles.id');
        $query->join('statuses', 'statuses.id', '=', 'users.user_status_id');
        $query->join('programs', 'programs.id', '=', 'model_has_roles.program_id');

        $query->where('roles.name', 'LIKE', config('roles.participant'));

        $query = $query->where(function ($query1) {
            $query1
            ->orWhereNull('users.join_reminder_at')
            ->orWhere('users.join_reminder_at', '<=', \Carbon\Carbon::now()->subDays(7)->toDateTimeString());
        });

        $query->where('users.user_status_id', '=', User::getIdStatusNew());

        return $query->get();
    }

    public function sendActivationReminderToParticipants()
    {
        $users = $this->getUsersToRemind();
        $programUsers = [];
        if($users->isNotEmpty())
        {
            foreach( $users as $user)
            {
                if( !isset($programUsers[$user->program_id]) )
                {
                    $programUsers[$user->program_id] = [];
                }
                $programUsers[$user->program_id][] = $user;
                $user->update(['join_reminder_at' => now()]);
                $user->token = \Illuminate\Support\Facades\Password::broker()->createToken($user);
            }
            $programIds = array_keys($programUsers);
            $programs = Program::whereIn('id', $programIds);
            foreach( $programUsers as $programId => $_users)
            {
                $program = $programs->find($programId);
                event( new \App\Events\UsersInvited( $_users, $program, true ) );
            }
        }
    }

    public function ssoAddToken($data, $ip): array
    {
        $ssoAllowedIps = json_decode(config('sso.sso_allowed_ips'), true);
        $message = '';

        if (in_array($ip, $ssoAllowedIps)) {
            $user = User::leftJoin('program_user', 'users.id', '=', 'program_user.user_id')
                ->select('users.*')
                ->where('program_user.program_id', $data['program_id'])
                ->where('users.email', $data['email'])
                ->first();

            if (is_object($user)) {
                $user->sso_token = $data['sso_token'];
                $success = $user->save();
                $code = 200;
            } else {
                $success = false;
                $code = 404;
                $message = 'User is not found';
            }
        } else {
            $success = false;
            $code = 403;
            $message = 'Access is denied';
        }
        return [
            'success' => $success,
            'message' => $message,
            'code' => $code,
        ];
    }

    public function getSsoUser($ssoToken): ?User
    {
        $user = null;
        if (!empty($ssoToken)) {
            $user = User::where('sso_token', $ssoToken)->first();
            if ($user) {
                $user->sso_token = null;
                $user->save();
            }
        }
        return $user;
    }
}
