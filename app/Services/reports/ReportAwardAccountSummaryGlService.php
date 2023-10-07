<?php

namespace App\Services\reports;

use App\Models\AccountType;
use App\Models\JournalEventType;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ReportAwardAccountSummaryGlService extends ReportServiceAbstract
{

    /**
     *
     * @inheritDoc
     */
    protected function getBaseQuery(): Builder
    {
        $selectedPrograms = $this->params[self::PROGRAM_IDS];
        $query = DB::table('users');
        $query->join('accounts', 'accounts.account_holder_id', '=', 'users.account_holder_id');
        $query->join('account_types', 'account_types.id', '=', 'accounts.account_type_id');
        $query->join('postings', 'postings.account_id', '=', 'accounts.id');
        $query->join('journal_events', 'journal_events.id', '=', 'postings.journal_event_id');
        $query->join('journal_event_types', 'journal_event_types.id', '=', 'journal_events.journal_event_type_id');
        $query->join('postings as program_posting', 'program_posting.journal_event_id', '=', 'journal_events.id');
        $query->join('accounts as program_accounts', 'program_accounts.id', '=', 'program_posting.account_id');
        $query->join('account_types as program_account_types', function ($join) {
            $join->on('program_account_types.id', '=', 'program_accounts.account_type_id');
            $join->on('program_account_types.name', '=', DB::raw("'" . AccountType::ACCOUNT_TYPE_MONIES_FEES . "'"));
        });
        $query->join('programs', 'programs.account_holder_id', '=', 'program_accounts.account_holder_id');
        $query->leftJoin('event_xml_data', 'event_xml_data.id', '=', 'journal_events.event_xml_data_id');
        $query->leftJoin('users as awarder', 'awarder.account_holder_id', '=',
            'event_xml_data.awarder_account_holder_id');
        $query->leftJoin('events', function ($join) use ($selectedPrograms) {
            $join->on('events.name', '=', 'event_xml_data.name')
                ->whereIn('events.program_id', $selectedPrograms);
        });
        $query->leftJoin('event_ledger_codes', 'event_ledger_codes.id', '=', 'events.ledger_code');

        $query->selectRaw("
            MAX(`programs`.id) as program_id,
            MAX(`programs`.name) as program_name,
            `postings`.id as posting_id,
            MAX(IF(
                (`postings`.`posting_amount` IS NOT NULL), `postings`.`posting_amount`, 0
            )) as `dollar_value`,
            MAX(`postings`.`created_at`) as `posting_timestamp`,
            MAX(IF(
                (`postings`.`posting_amount` IS NOT NULL),
                (`postings`.`posting_amount` * `programs`.`factor_valuation`), 0
            )) as `points`,
            MAX(`programs`.`factor_valuation`) as 'factor_valuation',
            `event_xml_data`.`name` as `event_name`,
            MAX(IF(
                (`program_posting`.`posting_amount` IS NOT NULL), `program_posting`.`posting_amount`, 0
            )) as `transaction_fee`,
            MAX(`event_ledger_codes`.`ledger_code`) as 'ledger_code',
            COALESCE(SUM(`postings`.posting_amount), 0) AS FIELD_TOTAL,
            COUNT(`journal_events`.id) AS `field_count`

        ");
        return $query;
    }

    /**
     * @inheritDoc
     */
    protected function setWhereFilters(Builder $query): Builder
    {
        $query->whereIn('account_types.name', [
            AccountType::getTypePointsAwarded(),
            AccountType::getTypeMoniesAwarded(),
        ]);
        $query->whereIn('journal_event_types.type', [
            JournalEventType::JOURNAL_EVENT_TYPES_AWARD_POINTS_TO_RECIPIENT,
            JournalEventType::JOURNAL_EVENT_TYPES_AWARD_MONIES_TO_RECIPIENT,
        ]);
        if ($this->params[self::DATE_FROM] && $this->params[self::DATE_TO]) {
            $query->whereBetween('postings.created_at', [$this->params[self::DATE_FROM], $this->params[self::DATE_TO]]);
        }
        $query->where('postings.is_credit', '=', true);
        $query->whereIn('programs.account_holder_id', $this->params[self::PROGRAMS]);
        return $query;
    }

    /**
     * @inheritDoc
     */
    protected function setGroupBy(Builder $query): Builder
    {
        $query->groupBy(['event_xml_data.name', 'postings.id']);
        return $query;
    }

    /**
     * @inheritDoc
     */
    protected function setOrderBy(Builder $query): Builder
    {
        $query->orderBy('event_xml_data.name');
        return $query;
    }

    protected function setLimit(Builder $query): Builder
    {
        return $query;
    }

    private function amountFormat($value){
        return number_format((float)$value, 2, '.', '');
    }
    public function getTable(): array
    {
        parent::getTable();

        $totalAwards = 0;
        $finalData = [];
        foreach ($this->table as $key => $awards) {
            $this->table[$key]->dollar_value = $this->amountFormat($this->table[$key]->dollar_value);
//            if ($key == 3) {
//                $awards->event_name = 'asd asd';
//            }
            $totalAwards += $awards->dollar_value;
            $finalData[$awards->program_name]['Property'] = $awards->program_name;
            $eventColumn = $awards->ledger_code ? $awards->event_name . '<br>' . $awards->ledger_code : $awards->event_name .'<br>!!!asd';
            $finalData[$awards->program_name][$eventColumn] =
                isset($finalData[$awards->program_name][$eventColumn]) ?
                    $this->amountFormat($finalData[$awards->program_name][$eventColumn] + $awards->dollar_value) : $awards->dollar_value;
        }

        foreach ($finalData as $finalDataKey => $programName) {
            $total = 0;
            foreach ($programName as $key => $value) {
                if ( ! in_array($key, ['Property', 'Total'])) {
                    $total = $this->amountFormat($total) + $this->amountFormat($value);
                }
            }
            $finalData[$finalDataKey]['Total'] = $this->amountFormat($total);
        }

        sort($finalData);
        $this->table = [];
        $this->table['data'] = $finalData;
        $this->table['total'] = count($finalData);
        $this->table['TotalAwards'] = $totalAwards;
        $this->table['TotalReclaimed'] = $this->totalReclaimed();

        return $this->table;
    }

    private function totalReclaimed(): float
    {
        $query = DB::table('accounts');
        $query->join('account_types', 'account_types.id', '=', 'accounts.account_type_id');
        $query->join('postings', 'postings.account_id', '=', 'accounts.id');
        $query->join('journal_events', 'journal_events.id', '=', 'postings.journal_event_id');
        $query->join('journal_event_types', 'journal_event_types.id', '=', 'journal_events.journal_event_type_id');

        $query->selectRaw("
            COALESCE(SUM(postings.posting_amount * postings.qty), 0) AS `field_value`
        ");

        $query->whereIn('account_types.name', [
            AccountType::ACCOUNT_TYPE_MONIES_DUE_TO_OWNER,
            AccountType::ACCOUNT_TYPE_MONIES_AVAILABLE,
        ]);
        $query->whereIn('journal_event_types.type', [
            JournalEventType::JOURNAL_EVENT_TYPES_RECLAIM_POINTS,
            JournalEventType::JOURNAL_EVENT_TYPES_RECLAIM_MONIES,
        ]);
        if ($this->params[self::DATE_FROM] && $this->params[self::DATE_TO]) {
            $query->whereBetween('postings.created_at', [$this->params[self::DATE_FROM], $this->params[self::DATE_TO]]);
        }
        $query->where('postings.is_credit', '=', true);
        $query->whereIn('accounts.account_holder_id', $this->params[self::PROGRAMS]);

        return (float)$query->get()[0]->field_value;
    }

    protected function getReportForCSV(): array
    {
        $this->isExport = true;
        $this->params[self::SQL_LIMIT] = null;
        $this->params[self::SQL_OFFSET] = null;
        $data = $this->getTable();

        if ($data) {
            $this->headersCount = count($data['data'][0]) - 2;

            $finalData = [];
            $tmp = [];
            $tmp['01'] = '';
            $tmp['02'] = 'Total for Period';
            $finalData[] = $tmp;
            $tmp = [];
            $tmp['01'] = 'Total Amount Awarded';
            $tmp['02'] = '$' . $data['TotalAwards'];
            $finalData[] = $tmp;
            $tmp = [];
            $tmp['01'] = 'Total Amount Reclaimed';
            $tmp['02'] = '$' . $data['TotalReclaimed'];
            $finalData[] = $tmp;
            $tmp = [];
            $tmp['01'] = '';
            $tmp['02'] = '';
            $finalData[] = $tmp;
            $tmp = [];
            $tmp['01'] = 'Reward Events Summary';
            $tmp['02'] = '';
            $finalData[] = $tmp;

            foreach ($data['data'] as $key => $item) {
                $tmp = [];
                $i = 1;
                foreach ($item as $subKey => $subItem){
                    $tmp['0'.$i] = $subKey;
                    $i++;
                }
                $finalData[] = $tmp;
                break;
            }

            foreach ($data['data'] as $key => $item) {
                $tmp = [];
                $i = 1;
                foreach ($item as $subKey => $subItem){
                    $tmp['0'.$i] = $subItem;
                    $i++;
                }
                $finalData[] = $tmp;
            }

            $data['data'] = $finalData;
            $data['total'] = count($finalData);
            $data['headers'] = $this->getCsvHeaders();
        }
        return $data;
    }

    public function getCsvHeaders(): array
    {
        $headers = [
            [
                "label" => "Program Balance for Period {$this->params[self::DATE_FROM]} to {$this->params[self::DATE_TO]}",
                'key' => '01'
            ],
            [
                'label' => '-',
                'key' => '02'
            ],
        ];
        for ($i = 3; $i < ($this->headersCount + 3); $i++){
            $headers[] = ['label' => '', 'key' => '0'.$i];
        }
        return $headers;
    }

}
