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

class ReportGoalProgressSummaryService extends ReportServiceAbstract
{
    public function getTable(): array
    {
        if (empty($this->table)) {
            $this->calc();
        }
        if ($this->params[self::PAGINATE]) {
            return [
                'data' => $this->table['data'],
                'count'=> $this->table['count']
            ];
        }
        return $this->table;
    }

    protected function calc(): array
    {
        $query = DB::table('goal_plans');
        $query->join('programs', 'programs.id', '=', 'goal_plans.program_id');
        $query->join('user_goals', 'user_goals.goal_plan_id', '=', 'goal_plans.id');      
        $query->join('user_goal_progress', 'user_goal_progress.user_goal_id', '=', 'user_goals.id');  
        $query->join('users', 'users.account_holder_id', '=', 'user_goals.user_id'); 
        $query->selectRaw("
            programs.name as program_name,
            goal_plans.name as goal_plan_name,
            users.organization_id,
            users.first_name,
            users.last_name,
            sum(user_goal_progress.progress_value) as progress_value,
            max(user_goal_progress.created_at) as created
        ");
        $query->where('goal_plans.program_id', $this->params[self::PROGRAM_ID]);
        $query->whereBetween("user_goal_progress.created_at",[$this->params[self::DATE_FROM], $this->params[self::DATE_TO]] );
        $query->orderBy('goal_plans.created_at', 'DESC');
        $table = $query->get();
        $this->table['data'] = $table;
        $this->table['count'] = $query->count();

        return $this->table;
    }

    public function getCsvHeaders(): array
    {
        return [
            [
                'label' => 'External ID',
                'key' => 'organization_id'
            ],
            [
                'label' => 'Program',
                'key' => 'program_name'
            ],
            [
                'label' => 'First Name',
                'key' => 'last_name'
            ],
            [
                'label' => 'Last Name',
                'key' => 'last_name'
            ],
            [
                'label' => 'Goal Plan',
                'key' => 'goal_plan_name'
            ],
            [
                'label' => "Last Goal Progress Date",
                'key' => "created",
            ],
            [
            'label' => "Total Goal Progress Value",
            'key' => "progress_value",
            ]
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
