<?php

namespace App\Services\reports;

use App\Models\AccountType;
use App\Models\JournalEventType;
use App\Models\MediumInfo;
use App\Models\Merchant;
use App\Models\OptimalValue;
use App\Models\Program;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Expr\Cast\Object_;
use stdClass;

class ReportParticipantStatusSummaryService extends ReportServiceAbstract
{
    private $total = [];

    protected function calc(): array
    {
        $programs = Program::whereIn('account_holder_id', $this->params[self::PROGRAMS])->get()->pluck('id')->toArray();
        $organizationId = 0;
        if($programs){
            $program = Program::find($programs[0]);
            $organizationId = $program->organization_id;
        }

        // Get user ids for report query to optimise query execution time
        $query = DB::table('users');
        $userClassForSql = str_replace('\\', '\\\\\\\\', get_class(new User));
        $query->addSelect('users.id');

        // subQuery
        $subQuery = DB::table('users');
        $subQuery->selectRaw("
            `programs`.name as 'program_name',
            `users`.account_holder_id,
            `users`.user_status_id,
            `statuses`.status
        ");
        $subQuery->join('program_user', 'program_user.user_id', '=', 'users.id');
        $subQuery->join('programs', function ($join) {
            $join->on('programs.organization_id', '=', 'users.organization_id');
            $join->on('programs.id', '=', 'program_user.program_id');
        });
        $subQuery->leftJoin('model_has_roles', function ($join) use ($userClassForSql) {
            $join->on('model_has_roles.model_id', '=', 'users.id');
            $join->on('model_has_roles.model_type', 'like', DB::raw("'" . $userClassForSql . "'"));
        });
        $subQuery->leftJoin('roles', 'roles.id', '=', 'model_has_roles.role_id');
        $subQuery->leftJoin('statuses', 'statuses.id', '=', 'users.user_status_id');
        $subQuery->whereIn('programs.id', $programs);
        $subQuery->whereIn('model_has_roles.program_id', $programs);
        $subQuery->where('users.organization_id', $organizationId);
        $subQuery->where('roles.name', 'LIKE', config('roles.participant'));
        $subQuery->groupBy('account_holder_id', 'program_name', 'user_status_id', 'status');

        // subQuery2
        $subQuery2 = DB::table(DB::raw("({$subQuery->toSql()}) as sub"));
        $subQuery2->selectRaw("
            program_name,
            COUNT(*) as all_count,
            account_holder_id,
            count(Distinct status) as unique_count,
            status
        ");
        $subQuery2->groupBy('program_name', 'status', 'account_holder_id');

        $query = DB::table(DB::raw("({$subQuery2->toSql()}) as sub2"));
        $query->selectRaw("
            program_name,
            sum(all_count) as count,
            sum(unique_count) as unique_count,
            status
        ");
        $query->mergeBindings($subQuery);

        $query = $this->setLimit($query);

        $query->groupBy('program_name', 'status');
        $query->orderBy('program_name', 'ASC');
        $query->having('count', '>', 0);

        $table = $query->get();

        $this->table['data'] = $table;
        $this->table['total'] =  $query->limit(9999999999)->offset(0)->count();

        return $this->table;
    }

    public function getCsvHeaders(): array
    {
        return [
            [
                'label' => 'Program Name',
                'key' => 'program_name'
            ],
            [
                'label' => 'Status',
                'key' => 'status'
            ],
            [
                'label' => 'count',
                'key' => 'count'
            ],
            [
                'label' => 'Unique Count',
                'key' => 'unique_count'
            ],
        ];
    }

    protected function getReportForCSV(): array
    {
        $this->isExport = true;
        $this->params[self::SQL_LIMIT] = null;
        $this->params[self::SQL_OFFSET] = null;
        $data = $this->getTable();

        $data['headers'] = $this->getCsvHeaders();
        return $data;
    }

}
