<?php

namespace App\Services\reports;

use App\Models\CsvImport;
use App\Models\Program;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Query\Builder;
use stdClass;

class ReportEngagementService extends ReportServiceAbstract
{

    // public function getTable(): array
    // {
    //     if (empty($this->table)) {
    //         $this->calc();
    //     }
    //     if ($this->params[self::PAGINATE]) {
    //         return [
    //             'data' => $this->table,
    //             'total' => $this->query instanceof Builder ? $this->query->count('referrals.id') : count($this->table),
    //         ];
    //     }
    //     return $this->table;
    // }

    protected function calc(): array
    {
        // $programs = Program::whereIn('account_holder_id', $this->params[self::PROGRAMS])->get();
        // $program = Program::find($this->params[self::PROGRAM_ID]);
        $query = DB::table('referrals');
        $query->join('programs', 'programs.id', '=', 'referrals.program_id');
        $query->join('users', 'users.id', '=', 'referrals.sender_id');

        $query->whereBetween('referrals.created_at', [$this->params[self::DATE_BEGIN], $this->params[self::DATE_END]]);
        $query->whereIn('programs.account_holder_id', $this->params[self::PROGRAMS]);
        $query->selectRaw("
        referrals.created_at as created,
        programs.name as program,
        CONCAT(users.first_name, ' ', users.last_name) as referrer,
        users.email as referrer_email,
        referrals.message as message,
        (CASE
            WHEN referrals.category_referral = 1 THEN 'Referral'
            WHEN referrals.category_feedback = 1 THEN 'Feedback'
            WHEN referrals.category_lead = 1 THEN 'Lead'
            WHEN referrals.category_reward = 1 THEN 'Reward'
            ELSE 'Unknown'
        END) AS category
        ");
        $query->orderBy('referrals.created_at', 'DESC');
        $table = $query->get();
        $this->table['data'] = $table;
        $this->table['total'] = count($table);

        return $this->table;
    }

    public function getCsvHeaders(): array
    {
        return [
            [
                'label' => 'Created',
                'key' => 'created'
            ],
            [
                'label' => 'Program',
                'key' => 'program'
            ],
            [
                'label' => 'Referrer',
                'key' => 'referrer'
            ],
            [
                'label' => 'Referrer Email',
                'key' => 'referrer_email'
            ],
            [
                'label' => 'Message',
                'key' => 'message'
            ],
            [
                'label' => 'Category',
                'key' => 'category'
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
