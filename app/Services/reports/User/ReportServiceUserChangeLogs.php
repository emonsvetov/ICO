<?php
namespace App\Services\reports\User;

use App\Models\AccountType;
use App\Models\JournalEventType;
use App\Models\Program;
use App\Models\User;
use App\Services\reports\ReportServiceAbstract as ReportServiceAbstractBase;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ReportServiceUserChangeLogs extends ReportServiceAbstractBase
{

    /**
     * @inheritDoc
     */
    protected function getBaseQuery(): Builder
    {
        $userClassForSql = str_replace('\\', '\\\\\\\\', get_class(new User));
        $query = DB::table('users_log');
        $query->join('users', 'users.account_holder_id', '=', 'users_log.user_account_holder_id');
        $query->join('program_user', 'program_user.user_id', '=', 'users.id');
        $query->join('programs', 'programs.id', '=', 'program_user.program_id');
        $query->join('model_has_roles', function ($join) use ($userClassForSql) {
            $join->on('model_has_roles.model_id', '=', 'users.id');
            $join->on('model_has_roles.model_type', 'like', DB::raw("'" . $userClassForSql . "'"));
        });
        $query->join('roles', 'model_has_roles.role_id', '=', 'roles.id');
        $query->join('technical_reasons', 'technical_reasons.id', '=', 'users_log.technical_reason_id');
        $query->leftJoin('statuses as status1', 'status1.id', '=', 'users_log.old_user_status_id');
        $query->leftJoin('statuses as status2', 'status2.id', '=', 'users_log.new_user_status_id');
        $query->leftJoin('users as ub', 'ub.account_holder_id', '=', 'users_log.updated_by');

        $query->selectRaw("
            `users_log`.user_account_holder_id
            , CONCAT(`users_log`.first_name, ' ', `users_log`.last_name) as name
            , CONCAT(ub.first_name, ' ', ub.last_name) as updated_by
            , `users_log`.email
            , `users_log`.id
            , `users_log`.type
            , `roles`.name as 'role'
            , `users_log`.updated_at
            , `users_log`.old_user_status_id
            , status1.status as old_user_status_label
            , `users_log`.new_user_status_id
            , status2.status as new_user_status_label
            , `programs`.id as program_id
            , `technical_reasons`.name as technical_reason
        ");

        return $query;
    }

    /**
     * @inheritDoc
     */
    protected function setWhereFilters(Builder $query): Builder
    {
        $program = Program::where('id', $this->params[self::PROGRAM_ID])->first();
        $topLevelProgram = $program->getRoot(['id', 'name']);
        $query->where('users_log.parent_program_id', '=', $topLevelProgram->id);
        $query->where('roles.name', 'LIKE', config('roles.participant'));
        return $query;
    }

}
