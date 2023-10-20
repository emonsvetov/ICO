<?php

namespace App\Services\reports;

use App\Models\AccountType;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ReportAwardSummaryParticipantsService extends ReportServiceAbstract
{

    /**
     * @inheritDoc
     */
    protected function getBaseQuery(): Builder
    {
        $userClassForSql = str_replace('\\', '\\\\\\\\', get_class(new User));
        $query = DB::table('users');
        $query->join('program_user', 'program_user.user_id', '=', 'users.id');
        $query->join('programs', 'programs.id', '=', 'program_user.program_id');
        $query->join('model_has_roles', function ($join) use ($userClassForSql) {
            $join->on('model_has_roles.model_id', '=', 'users.id');
            $join->on('model_has_roles.model_type', 'like', DB::raw("'" . $userClassForSql . "'"));
        });
        $query->join('roles', 'model_has_roles.role_id', '=', 'roles.id');
        $query->join('statuses', 'statuses.id', '=', 'users.user_status_id');

        $query->addSelect([
            'programs.account_holder_id as program_id',
            'users.account_holder_id as user_id',
            'users.email',
            'users.first_name',
            'users.last_name',
            'users.user_status_id',
            'statuses.status',
        ]);
        $query->distinct();
        return $query;
    }

    /**
     * @inheritDoc
     */
    protected function setWhereFilters(Builder $query): Builder
    {
        $query->where('roles.name', 'LIKE', config('roles.participant'));
        $query->whereIn('programs.account_holder_id', $this->params[self::PROGRAMS]);
        return $query;
    }

    public function getTable(): array
    {
        if (empty($this->table)) {
            $this->calc();
        }
        if ($this->params[self::PAGINATE]) {
            return [
                'data' => $this->table,
                'total' => $this->query instanceof Builder ? $this->query->count('users.id') : count($this->table),
            ];
        }
        return $this->table;
    }

}
