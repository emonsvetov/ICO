<?php

namespace App\Services\reports;

use App\Models\AccountType;
use App\Models\JournalEventType;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ReportParticipantAccountSubprogramService extends ReportServiceAbstract
{

    /**
     * @inheritDoc
     */
    protected function getBaseQuery(): Builder
    {
        $userClassForSql = str_replace('\\', '\\\\\\\\', get_class(new User));
        $query = DB::table('users');
        $query->join('model_has_roles', function ($join) use ($userClassForSql) {
            $join->on('model_has_roles.model_id', '=', 'users.id');
            $join->on('model_has_roles.model_type', 'like', DB::raw("'" . $userClassForSql . "'"));
        });
        $query->join('roles', 'model_has_roles.role_id', '=', 'roles.id');
        $query->join('program_user', 'program_user.user_id', '=', 'users.id');
        $query->join('programs', 'programs.id', '=', 'program_user.program_id');

        $query->selectRaw("
            programs.id as program_id,
            users.account_holder_id AS recipient_id,
            users.activated,
            users.created_at as created,
            users.email AS recipient_email,
            users.first_name AS recipient_first_name,
            users.last_name AS recipient_last_name,
            users.organization_id AS recipient_organization_uid,
            users.hire_date AS anniversary,
            users.user_status_id,
            programs.external_id,
            programs.name as program_name,
            programs.account_holder_id as program_account_holder_id
        "
        );

        $query->distinct();

        return $query;
    }

    /**
     * @inheritDoc
     */
    protected function setWhereFilters(Builder $query): Builder
    {
        $query->whereIn('programs.account_holder_id', $this->params[self::PROGRAMS]);
        $query->where('programs.id', $this->params[self::PROGRAM_ID]);
        return $query;
    }

    /**
     * @inheritDoc
     */
    protected function setOrderBy(Builder $query): Builder
    {
        parent::setOrderBy($query);

        $query->orderBy('program_account_holder_id');
        $query->orderBy('recipient_last_name');
        $query->orderBy('recipient_first_name');
        return $query;
    }

    /**
     * @inheritDoc
     */
    public function getCsvHeaders(): array
    {
        return [
            [
                'label' => 'Program ID',
                'key' => 'program_id',
            ],
            [
                'label' => 'Program',
                'key' => 'program_name',
            ],
            [
                'label' => 'External ID',
                'key' => 'external_id',
            ],
            [
                'label' => 'Org ID',
                'key' => 'recipient_organization_uid',
            ],
            [
                'label' => 'First Name',
                'key' => 'recipient_first_name',
            ],
            [
                'label' => 'Last Name',
                'key' => 'recipient_last_name',
            ],
            [
                'label' => 'Email',
                'key' => 'recipient_email',
            ],
        ];
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
