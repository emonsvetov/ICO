<?php
namespace App\Services;
use App\Http\Requests\UserRequest;
use App\Models\AccountType;
use App\Models\Program;
use App\Models\Role;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Traits\Filterable;
use App\Models\Traits\UserFilters;
use App\Models\Status;
use App\Models\User;
use DB;
use App\Http\Traits\MediaUploadTrait;

class UserService
{
    use Filterable, UserFilters, MediaUploadTrait;

    private AccountService $accountService;

    public function __construct(AccountService $accountService) {
        $this->accountService = $accountService;
    }

    public function getSuperAdmins( $paginate = false )   {
        $query = User::whereHas('roles', function (Builder $query) {
            $query->where('name', 'LIKE', config('roles.super_admin'));
        });
        if( $paginate ) {
            return $query->paginate();
        }   else    {
            return $query->get();
        }
    }

    public function getParticipants($program, $paginate = false)   {
        $program = self::GetModelByMixed($program);
        if( !$program->exists() ) return;
        // DB::enableQueryLog();
        self::$query = User::whereHas('roles', function (Builder $query) use($program) {
            $query->where('name', 'LIKE', config('roles.participant'))
            ->where('model_has_roles.program_id', $program->id);
        });
        self::_makeParams();
        self::applyFilters();
        if( $paginate ) {
            $users = self::$query->paginate( self::$PARAMS['limit'] );
        }   else    {
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
        $user->roles()->wherePivot('program_id','=',0)->detach();
        foreach($roles as $role_id)    {
            $newRoles[$role_id] = $columns;
        }
        $user->roles()->attach( $newRoles );
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
}
