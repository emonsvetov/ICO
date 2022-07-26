<?php
namespace App\Services;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Traits\Filterable;
use App\Models\Traits\UserFilters;
use App\Models\Status;
use App\Models\User;
use DB;

class UserService 
{
    use Filterable, UserFilters;

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
}
