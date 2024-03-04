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

class ReportUserDetailChangeLogService extends ReportServiceAbstract
{
    protected function calc(): array
    {
        $this->table = [];
        $userClassForSql = str_replace('\\', '\\\\\\\\', get_class(new User));
        $userString = "Users";
        $tempArray = [];
        $start_date = $this->params[self::DATE_BEGIN];
        $end_date = $this->params[self::DATE_END];

		if (is_array ( $this->params[self::PROGRAMS] ) && count ( $this->params[self::PROGRAMS] ) > 0) {
			$programs = Program::read_programs ( $this->params [self::PROGRAMS], true );
            if ( $programs->isNotEmpty() ) {
                foreach ( $programs as $program ) {
                    $program = Program::find($program->id);
                    $parent_program_id =  $program->get_top_level_program_id($program->id);
                    $userStatesSql = "SELECT statuses.id, statuses.status from statuses where statuses.id > 0 AND statuses.context = '".$userString."'";

                    $sql = " SELECT ul.updated_at, ul.user_account_holder_id, CONCAT(ul.first_name, ' ', ul.last_name) as name, CONCAT(ub.first_name, ' ', ub.last_name) as updated_by, ul.email,ul.id,ul.type, statuses.status as role, ul.updated_at, ul.old_user_status_id, state1.status as old_user_state_label, ul.new_user_status_id, state2.status as new_user_state_label, tr.name as technical_reason FROM users_log as ul LEFT JOIN users as ub ON ub.id = ul.updated_by LEFT JOIN  model_has_roles ON model_has_roles.model_id =ub.id LEFT JOIN roles ON roles.id = model_has_roles.role_id LEFT JOIN statuses ON statuses.id = ub.user_status_id LEFT JOIN technical_reasons AS tr ON tr.id = ul.technical_reason_id LEFT JOIN ($userStatesSql) as state1 ON ul.old_user_status_id = state1.id LEFT JOIN ($userStatesSql) as state2 ON ul.new_user_status_id = state2.id WHERE ul.parent_program_id = {$parent_program_id} AND DATE_FORMAT(ul.updated_at,'%Y-%m-%d H:i:s') >= DATE_FORMAT('{$start_date} 00:00:00','%Y-%m-%d H:i:s')
                    AND DATE_FORMAT(ul.updated_at,'%Y-%m-%d H:i:s') <= DATE_FORMAT('{$end_date} 23:59:59','%Y-%m-%d H:i:s')
                    GROUP BY ul.id";
                    $this->table[$program->id] = DB::select($sql);
				}
            }
            $this->table['data'] = $this->table[$programs[0]->id];
            $this->table['total'] = count($tempArray);
        }

        return $this->table;

    }
    public function getCsvHeaders(): array
    {

        return [
            [
                'label'=> "Name",
                'key'=> "name",
            ],
            [
                'label'=> "Email",
                'key'=> "email"
            ],
            [
                'label'=> "Type",
                'key'=> "type"
            ],
            [
                'label'=> "Old Value",
                'key'=> "old_user_state_label"
            ],
            [
                'label'=> "New Value",
                'key'=> "new_user_state_label"
            ],
            [
                'label'=> "Technical Reason",
                'key'=> "technical_reason"
            ],
            [
                'label'=> "Updated By",
                'key'=> "updated_by"
            ],
            [
                'label'=> "Updated At",
                'key'=> "updated_at"
            ],
        ];
    }

    protected function getReportForCSV(): array
    {
        $data = $this->getTable();
        $data['headers'] = $this->getCsvHeaders();
        return $data;
    }
}
