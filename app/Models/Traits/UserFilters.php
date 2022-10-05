<?php
namespace App\Models\Traits;

// use App\Models\Traits\GetModelByMixed;
use App\Models\Traits\Filterable;
use DB;

trait UserFilters
{
    // use Filterable;

    protected function applyFilters()    {
        if(!empty(self::$PARAMS['status']))    {
            $statuses = explode(',', self::$PARAMS['status']);
            self::$query = self::$query->join('statuses', 'statuses.id', '=', 'users.user_status_id')
            ->whereIn('statuses.status', $statuses)
            ->where('statuses.context', '=', 'Users')
            ->select('users.*')
            ;
        }
        if(!empty(self::$PARAMS['keyword'])) {
            $keyword = self::$PARAMS['keyword'];
            self::$query = self::$query->where(function($query1) use($keyword) {
                $query1->orWhere('users.id', 'LIKE', "%{$keyword}%")
                ->orWhere('users.email', 'LIKE', "%{$keyword}%")
                ->orWhere(DB::raw("CONCAT(first_name, ' ', last_name)"), 'LIKE', "%{$keyword}%");
                // ->orWhere('users.first_name', 'LIKE', "%{$keyword}%")
                // ->orWhere('users.last_name', 'LIKE', "%{$keyword}%");
            });
        }
        if(!empty(self::$PARAMS['except'])) {
            $users = explode(',', self::$PARAMS['except']);
            self::$query = self::$query->whereNotIn('id', $users);
        }
    }

    protected function applyFilter()   {
    }
}
