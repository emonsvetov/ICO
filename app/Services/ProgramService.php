<?php
namespace App\Services;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Traits\IdExtractor;
use App\Models\Role;
use App\Models\User;

class ProgramService 
{
    use IdExtractor;

    public function getParticipants($program, $paginate = false)   {
        $program_id = self::extractId($program);
        if( !$program_id ) return;
        $query = User::whereHas('roles', function (Builder $query) use($program_id) {
            $query->where('name', 'LIKE', config('roles.participant'))
            ->where('model_has_roles.program_id', $program_id);
        });
        if( $paginate ) {
            return $query->paginate();
        }   else    {
            return $query->get();
        }
    }
}
