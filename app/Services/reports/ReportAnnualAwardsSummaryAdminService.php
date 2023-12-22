<?php

namespace App\Services\reports;

use App\Services\Report\ReportServiceAwardAudit;
use App\Services\Report\ReportServiceSumBudget;
use App\Services\Report\ReportServiceSumByAccountAndJournalEvent;
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

   public function getTable(): array
    {
        $dataFromParent = parent::getTable();
        $month = $this->params['month']?? 1;
        $year = (int)$this->params['year'] ?? date('Y');
        $monthName = date('F', mktime(0, 0, 0, $month, 1));
        $columans = [
            [
                'title' => '',
                'dataIndex' => 'financial_summary',
                'width' => 300,
                'key' => 'financial_summary',
            ],
            [
                'title' => $monthName . ' ' . $year-1,
                'dataIndex' => strtolower($monthName . '_' . $year-1),
                'width' => 400,
                'key' => strtolower($monthName . '_' . $year-1),
            ],
            [
                'title' => $monthName . ' ' . $year,
                'dataIndex' => strtolower($monthName . '_' . $year),
                'width' => 400,
                'key' => strtolower($monthName . '_' . $year),
            ],
            [
                'title' => $year-1,
                'dataIndex' => $year-1,
                'width' => 400,
                'key' => $year-1,
            ],
            [
                'title' => $year,
                'width' => 400,
                'dataIndex' => $year,
                'key' => $year,
            ],
        ];

        $awardsData = [];
        $annualData = 0;
        $previousYearAnnualData = 0;
        $monthData = 0;
        $previousYearMonthData = 0;

        $annualTotal = 0;
        $previousYearAnnualTotal = 0;
        $monthTotal = 0;
        $previousYearMonthTotal = 0;

        foreach (self::OPTIONS_AWARDS as $key => $val) {

            if (is_object($dataFromParent[$key])){
                if (is_array($dataFromParent[$key]->annual)) {
                    $annualData = 0;
                }else{
                    $annualData = $dataFromParent[$key]->annual;
                }

                if (is_array($dataFromParent[$key]->previous_year_annual)) {
                    $previousYearAnnualData = 0;
                }else{
                    $previousYearAnnualData = $dataFromParent[$key]->previous_year_annual;
                }

                if (is_array($dataFromParent[$key]->month)) {
                    $monthData = 0;
                }else{
                    $monthData = $dataFromParent[$key]->previous_year_annual;
                }

                if (is_array($dataFromParent[$key]->previous_year_month)) {
                    $previousYearMonthData = 0;
                }else{
                    $previousYearMonthData = $dataFromParent[$key]->previous_year_month;
                }

            }else{
                $annualData = $dataFromParent[$key]['annual'];
                $previousYearAnnualData = $dataFromParent[$key]['previous_year_annual'];
                $monthData = $dataFromParent[$key]['month'];
                $previousYearMonthData =$dataFromParent[$key]['previous_year_month'];
            }

            $awardsData[] = [
                'key' => $key,
                'financial_summary' => $val,
                strtolower($monthName . '_' . $year - 1) => (float) $previousYearMonthData,
                strtolower($monthName . '_' . $year) => (float)$monthData,
                $year - 1 => (float)$previousYearAnnualData,
                $year . '' => (float)$annualData,
            ];

            $annualTotal+=$annualData;
            $previousYearAnnualTotal+=$previousYearAnnualData;
            $monthTotal+=$monthData;
            $previousYearMonthTotal+=$previousYearMonthData;
        }

        $awardsTotal = [
            'key' => 'awards_total',
            'financial_summary' => 'Remaining Budget',
            strtolower($monthName . '_' . $year - 1) => $previousYearMonthTotal,
            strtolower($monthName . '_' . $year) => $monthTotal,
            $year - 1 => $previousYearAnnualTotal,
            $year . '' => $annualTotal,
        ];

        $annualTotal = 0;
        $previousYearAnnualTotal = 0;
        $monthTotal = 0;
        $previousYearMonthTotal = 0;

        $rewardData = [];
        foreach ($dataFromParent['event_summary_program_reward'] as $key => $val) {

            $previousYearMonthTotal+=$val->previous_year_month;
            $monthTotal+=$val->month;
            $previousYearAnnualTotal+=$val->previous_year_annual;
            $annualTotal+=$val->annual;

            $rewardData[] = [
                'key' => $key,
                'financial_summary' => $val->event_name,
                strtolower($monthName . '_' . $year - 1) => round($val->previous_year_month,2),
                strtolower($monthName . '_' . $year) => round($val->month,2),
                $year - 1 => round($val->previous_year_annual,2),
                $year . '' => round($val->annual,2),
            ];
        }

        $rewardTotal = [
            'key' => 'reward_total',
            'financial_summary' => 'Total',
            strtolower($monthName . '_' . $year - 1) => round($previousYearMonthTotal,2),
            strtolower($monthName . '_' . $year) => round($monthTotal,2),
            $year - 1 => round($previousYearAnnualTotal,2),
            $year . '' => round($annualTotal,2),
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

}
