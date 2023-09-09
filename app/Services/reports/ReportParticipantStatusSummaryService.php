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
        $program = Program::findOrFail($this->params[self::PROGRAM_ID]);

        // Get user ids for report query to optimise query execution time
        $query = DB::table('users');
        $userClassForSql = str_replace('\\', '\\\\\\\\', get_class(new User));
        $query->join('model_has_roles', function ($join) use ($userClassForSql) {
            $join->on('model_has_roles.model_id', '=', 'users.id');
            $join->on('model_has_roles.model_type', 'like', DB::raw("'" . $userClassForSql . "'"));
        });
        $query->join('roles', 'roles.id', '=', 'model_has_roles.role_id');
        $query->addSelect('users.id');
        $query->where('roles.name', 'LIKE', config('roles.participant'));
        $query->whereIn('model_has_roles.program_id', $programs);

        // subQuery
        $subQuery = DB::table('users');
        $subQuery->selectRaw("
            `users`.account_holder_id,
            `users`.user_status_id,
            `statuses`.status
        ");
        $subQuery->leftJoin('model_has_roles', function ($join) use ($userClassForSql) {
            $join->on('model_has_roles.model_id', '=', 'users.id');
            $join->on('model_has_roles.model_type', 'like', DB::raw("'" . $userClassForSql . "'"));
        });
        $subQuery->leftJoin('roles', 'roles.id', '=', 'model_has_roles.role_id');
        $subQuery->leftJoin('programs', 'programs.id', '=', 'model_has_roles.program_id');
        $subQuery->leftJoin('statuses', 'statuses.id', '=', 'users.user_status_id');
        $subQuery->whereIn('model_has_roles.program_id', $programs);

        // subQuery2
        $subQuery2 = DB::table(DB::raw("({$subQuery->toSql()}) as sub"));
        $subQuery2->selectRaw("
            COUNT(*) as all_count,
            account_holder_id,
            count(Distinct status) as unique_count,
            status
        ");
        $subQuery2->groupBy('status', 'account_holder_id');

        $query = DB::table(DB::raw("({$subQuery2->toSql()}) as sub2"));
        $query->selectRaw("
            sum(all_count) as count,
            sum(unique_count) as unique_count,
            status
        ");
        $query->mergeBindings($subQuery);

        $query = $this->setLimit($query);

        $query->groupBy('status');
        $query->having('count', '>', 0);

        $table = $query->get();

        $this->table['data'] = $table;
        $this->table['total'] =  $query->count();

        return $this->table;
    }

    public function getCsvHeaders(): array
    {
        return [
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
