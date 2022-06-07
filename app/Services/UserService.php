<?php
namespace App\Services;
use Illuminate\Database\Eloquent\Builder;
use App\Models\User;

class UserService 
{
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
}
