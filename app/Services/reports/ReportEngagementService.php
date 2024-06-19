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

    protected function getBaseQuery(): Builder
    {
        // $programs = Program::whereIn('account_holder_id', $this->params[self::PROGRAMS])->get();
        // $program = Program::find($this->params[self::PROGRAM_ID]);

        $query = DB::table('referrals');
        $query->join('programs', 'programs.id', '=', 'referrals.program_id');
        $query->leftJoin('users', 'users.id', '=', 'referrals.sender_id');

        $query->whereBetween('referrals.created_at', [$this->params[self::DATE_BEGIN], $this->params[self::DATE_END]]);
        $query->whereIn('programs.account_holder_id', $this->params[self::PROGRAMS]);
        $query->selectRaw("
        referrals.created_at as created,
        programs.name as program,
        COALESCE(CONCAT(users.first_name, ' ', users.last_name), CONCAT(referrals.sender_first_name, ' ', referrals.sender_last_name)) AS referrer,
        COALESCE(users.email, referrals.sender_email) AS referrer_email,
        CONCAT(referrals.recipient_first_name, ' ', referrals.recipient_last_name) as referree,
        referrals.recipient_email as referree_email,
        referrals.message as message,
        (CASE
            WHEN referrals.category_referral = 1 THEN 'Referral'
            WHEN referrals.category_feedback = 1 THEN 'Feedback'
            WHEN referrals.category_lead = 1 THEN 'Lead'
            WHEN referrals.category_reward = 1 THEN 'Reward'
            ELSE 'Unknown'
        END) AS category
        ");

        return $query;
    }

    protected function calc()
    {
        $this->table = [];
        $query = $this->getBaseQuery();
        $this->query = $query;
        $query = $this->setWhereFilters($query);
        $query = $this->setGroupBy($query);
        $query = $this->setOrderBy($query);
        $total = count($query->get()->toArray());
        $query = $this->setLimit($query);
        $this->table['data'] = $query->get()->toArray();
        $this->table['total'] = $total;
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
