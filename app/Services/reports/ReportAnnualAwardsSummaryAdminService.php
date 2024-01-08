<?php

namespace App\Services\reports;

use App\Services\Report\ReportServiceAwardAudit;
use App\Services\Report\ReportServiceSumBudget;
use App\Services\Report\ReportServiceSumByAccountAndJournalEvent;
use Illuminate\Support\Facades\DB;
use stdClass;

class ReportAnnualAwardsSummaryAdminService extends ReportAnnualAwardsSummaryService
{
    const OPTIONS_AWARDS = [
        'event_summary_program_budget' => 'Program Budget',
        'event_summary_points_awarded' => 'Amount Awarded',
        'event_summary_transaction_fees' => 'Transaction Fees',
        'event_summary_program_reclaimed' => 'Amount Reclaimed',
    ];

    const OPTIONS_REWARD = [
        'award' => 'Award',
        'transaction_fees' => 'Transaction Fees',
        'amount_reclaimed' => 'Amount Reclaimed',
    ];

    public function getData($filters)
    {
        $results = DB::table('programs')
            ->join('events', 'programs.id', '=', 'events.program_id')
            ->join('event_xml_data', 'event_xml_data.event_template_id', '=', 'events.id')
            ->join('journal_events', 'journal_events.event_xml_data_id', '=', 'event_xml_data.id')
            ->join('postings', 'postings.journal_event_id', '=', 'journal_events.id')
            ->select('events.id', 'events.name', 'postings.posting_amount', 'postings.created_at')
            ->distinct()
            ->where('account_holder_id', $filters['account_holder_id'])
            ->whereYear('postings.created_at', $filters['year']);

        if (isset($filters['month'])) {
            $results->whereMonth('postings.created_at', $filters['month']);
        }

        $results = $results
            ->orderByDesc('postings.created_at')
            ->orderBy('postings.posting_amount')
            ->orderBy('events.id')
            ->get();

        $res = [];
        foreach ($results as $val) {
            if (!isset($res[$val->name])) {
                $res[$val->name] = 0;
            }
            $res[$val->name] += $val->posting_amount;
        }

        return $res;
    }

    public static function sumValues($values)
    {
        if ($values) {
            return round(array_sum($values), 2);
        } else {
            return 0;
        }
    }

    public function getTable(): array
    {
        $yearMonthData = $this->getData([
            'account_holder_id' => $this->params['program_account_holder_ids'],
            'year' => (int)$this->params['year'] ?? date('Y'),
            'month' => $this->params['month'] ?? 1,
        ]);

        $previousYearMonthData = $this->getData([
            'account_holder_id' => $this->params['program_account_holder_ids'],
            'year' => (int)$this->params['year'] - 1 ?? date('Y'),
            'month' => $this->params['month'] ?? 1,
        ]);

        $previousYearData = $this->getData([
            'account_holder_id' => $this->params['program_account_holder_ids'],
            'year' => (int)$this->params['year'] - 1 ?? date('Y'),
        ]);

        $yearData = $this->getData([
            'account_holder_id' => $this->params['program_account_holder_ids'],
            'year' => (int)$this->params['year'] ?? date('Y'),
        ]);

        $events = array_merge($yearMonthData, $previousYearMonthData, $previousYearData, $yearData);

        $month = $this->params['month'] ?? 1;
        $year = (int)$this->params['year'] ?? date('Y');
        $monthName = date('F', mktime(0, 0, 0, $month, 1));
        $columans = [
            [
                'title' => ' ',
                'dataIndex' => 'financial_summary',
                'width' => 300,
                'key' => 'financial_summary',
            ],
            [
                'title' => $monthName . ' ' . $year - 1,
                'dataIndex' => strtolower($monthName . '_' . $year - 1),
                'width' => 400,
                'key' => strtolower($monthName . '_' . $year - 1),
            ],
            [
                'title' => $monthName . ' ' . $year,
                'dataIndex' => strtolower($monthName . '_' . $year),
                'width' => 400,
                'key' => strtolower($monthName . '_' . $year),
            ],
            [
                'title' => $year - 1,
                'dataIndex' => $year - 1,
                'width' => 400,
                'key' => $year - 1,
            ],
            [
                'title' => $year,
                'width' => 400,
                'dataIndex' => $year,
                'key' => $year,
            ],
        ];

        $awardsData = [];

        foreach (self::OPTIONS_AWARDS as $key => $val) {
            $previousMonthValue = 0;
            $currentMonth = 0;
            $previousYearValue = 0;
            $currentYear = 0;

            if ($key == 'event_summary_points_awarded') {
                $previousMonthValue = self::sumValues($previousYearMonthData);
                $currentMonth = self::sumValues($yearMonthData);
                $previousYearValue = self::sumValues($previousYearData);
                $currentYear = self::sumValues($yearData);
            }

            $awardsData[] = [
                'key' => $key,
                'financial_summary' => $val,
                strtolower($monthName . '_' . $year - 1) => (float)$previousMonthValue,
                strtolower($monthName . '_' . $year) => (float)$currentMonth,
                $year - 1 => (float)$previousYearValue,
                $year . '' => (float)$currentYear,
            ];
        }
        $eventSummary = [];
        foreach ($awardsData as $key => $value) {
            if ($value['key'] == 'event_summary_program_budget') {
                $awardsTotal = [
                    'key' => 'awards_total',
                    'financial_summary' => 'Remaining Budget',
                    strtolower($monthName . '_' . $year - 1) => $value[strtolower($monthName . '_' . $year - 1)],
                    strtolower($monthName . '_' . $year) => $value[strtolower($monthName . '_' . $year)],
                    $year - 1 => $value[$year - 1],
                    $year . '' => $value[$year . ''],
                ];
            } else {
                $awardsTotal[strtolower($monthName . '_' . $year - 1)] -= $value[strtolower($monthName . '_' . $year - 1)];
                $awardsTotal[strtolower($monthName . '_' . $year)] -= $value[strtolower($monthName . '_' . $year)];
                $awardsTotal[$year - 1] -= $value[$year - 1];
                $awardsTotal[$year . ''] -= $value[$year . ''];
            }

            if ($value['key'] == 'event_summary_transaction_fees') {
                $eventSummary[] = $value;
            }
            if ($value['key'] == 'event_summary_program_reclaimed') {
                $eventSummary[] = $value;
            }
        }


        $annualTotal = 0;
        $previousYearAnnualTotal = 0;
        $monthTotal = 0;
        $previousYearMonthTotal = 0;

        $rewardData = [];
        foreach ($events as $key => $val) {
            $rewardData[] = [
                'key' => $key,
                'financial_summary' => $key,
                strtolower($monthName . '_' . $year - 1) => isset($previousYearMonthData[$key]) ? $previousYearMonthData[$key] : 0,
                strtolower($monthName . '_' . $year) => isset($yearMonthData[$key]) ? $yearMonthData[$key] : 0,
                $year - 1 => isset($previousYearData[$key]) ? $previousYearData[$key] : 0,
                $year . '' => isset($yearData[$key]) ? $yearData[$key] : 0,
            ];

            $previousYearMonthTotal += isset($previousYearMonthData[$key]) ? $previousYearMonthData[$key] : 0;
            $monthTotal += isset($yearMonthData[$key]) ? $yearMonthData[$key] : 0;
            $previousYearAnnualTotal += isset($previousYearData[$key]) ? $previousYearData[$key] : 0;
            $annualTotal += isset($yearData[$key]) ? $yearData[$key] : 0;
        }

        foreach ($eventSummary as $value) {
            $rewardData[] = $value;
        }

        $rewardTotal = [
            'key' => 'reward_total',
            'financial_summary' => 'Total',
            strtolower($monthName . '_' . $year - 1) => round($previousYearMonthTotal, 2),
            strtolower($monthName . '_' . $year) => round($monthTotal, 2),
            $year - 1 => round($previousYearAnnualTotal, 2),
            $year . '' => round($annualTotal, 2),
        ];

        return [
            'awards' => [
                'title' => 'Program Budget VS Awards ' . $monthName . ' ' . $year,
                'columans' => $columans,
                'data' => $awardsData,
                'total' => $awardsTotal,
            ],
            'rewards' => [
                'title' => 'Reward Events Summary ' . $monthName . ' ' . $year,
                'columans' => $columans,
                'data' => $rewardData,
                'total' => $rewardTotal,
            ],
        ];
    }

    public function getCsvHeaders(): array
    {
        return [];
    }

    protected function getReportForCSV(): array
    {
        $data = $this->getTable();
        $csvData = [];
        $csvData = array_merge($csvData, $this->processSection($data['awards']));
        $csvData[] = [];
        $csvData = array_merge($csvData, $this->processSection($data['rewards']));

        return $csvData;
    }

    private function processSection(array $sectionData): array
    {
        $csvData = [];
        $csvData[] = [0 => $sectionData['title']];

        $csvHeaders = array_map(function ($column) {
            return $column['title'];
        }, $sectionData['columans']);

        $csvData[] = $csvHeaders;

        foreach ($sectionData['data'] as $value) {
            $row = array_map(function ($col) use ($value) {
                return $value[$col['dataIndex']];
            }, $sectionData['columans']);

            $csvData[] = $row;
        }

        $total = $sectionData['total'];
        $row = array_map(function ($col) use ($total) {
            return $total[$col['dataIndex']];
        }, $sectionData['columans']);

        $csvData[] = $row;

        return $csvData;
    }
}
