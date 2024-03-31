<?php

namespace App\Services\reports;

use App\Models\Program;
use App\Models\ProgramBudget;
use Illuminate\Support\Facades\DB;
use stdClass;

class ReportBudgetComparisonService extends ReportServiceAbstract
{
    protected function calc(): array
    {
        $table = [];

        $programs = Program::whereIn('account_holder_id', $this->params[self::PROGRAMS])->get();
        $programIds = $programs->pluck('id')->toArray();

        for ($i = 0; $i < 12; ++$i) {
            $month = new stdClass ();
            $month->budget = 0;
            $month->awarded = 0;
            $month->variance = 0;
            $month->month = $i + 1;
            $table[] = $month;
        }

        $query = DB::table('program_budget');
        $query->addSelect(DB::raw("
            COALESCE(SUM(budget), 0) as budget,
            month,
            year
        "));
        $query->whereIn('program_budget.program_id', $programIds);
        $query->where('program_budget.year', $this->params[self::YEAR]);
        $query->groupBy(['month', 'year']);
        $comparisonBudget = $query->get();

        foreach ($comparisonBudget as $item) {
            $table[$item->month - 1]->budget += $item->budget;
        }

        // Get all of the awards
        $className = ReportAwardSummaryAwardsBudgetService::class;
        $awardsReport = (new $className($this->params))->getReport();

        if (isset($awardsReport['data']) && $awardsReport['data']) {
            foreach ($awardsReport['data'] as $item) {
                $table[0]->awarded += $item->month1_value;
                $table[1]->awarded += $item->month2_value;
                $table[2]->awarded += $item->month3_value;
                $table[3]->awarded += $item->month4_value;
                $table[4]->awarded += $item->month5_value;
                $table[5]->awarded += $item->month6_value;
                $table[6]->awarded += $item->month7_value;
                $table[7]->awarded += $item->month8_value;
                $table[8]->awarded += $item->month9_value;
                $table[9]->awarded += $item->month10_value;
                $table[10]->awarded += $item->month11_value;
                $table[11]->awarded += $item->month12_value;
            }
        }

        // Get all of the reclaims
        $className = ReportAwardSummaryReclaimsBudgetService::class;
        $reclaimsReport = (new $className($this->params))->getReport();

        if (isset($reclaimsReport['data']) && $reclaimsReport['data']) {
            foreach ($reclaimsReport['data'] as $item) {
                $table[0]->awarded -= $item->month1_value;
                $table[1]->awarded -= $item->month2_value;
                $table[2]->awarded -= $item->month3_value;
                $table[3]->awarded -= $item->month4_value;
                $table[4]->awarded -= $item->month5_value;
                $table[5]->awarded -= $item->month6_value;
                $table[6]->awarded -= $item->month7_value;
                $table[7]->awarded -= $item->month8_value;
                $table[8]->awarded -= $item->month9_value;
                $table[9]->awarded -= $item->month10_value;
                $table[10]->awarded -= $item->month11_value;
                $table[11]->awarded -= $item->month12_value;
            }
        }

        // Final calculations, calc the variance
        for($i = 0; $i < 12; ++ $i) {
            $table[$i]->variance = $table[$i]->budget - $table[$i]->awarded;
        }

        $this->table['data'] = $table;
        $this->table['total'] = count($table);

        return $this->table;
    }

    public function getCsvHeaders(): array
    {
        if ($this->params[self::SERVER] === 'program') {
            return [
                [
                    'label' => 'Month',
                    'key' => 'month',
                ],
                [
                    'label' => 'Program Name',
                    'key' => 'program_name',
                ],
                [
                    'label' => 'Program Id',
                    'key' => 'program_id',
                ],
                [
                    'label' => 'Budget Value',
                    'key' => 'budget_value',
                ],
            ];
        } else {
            return [];
        }
    }

    protected function getReportForCSV(): array
    {
        $this->isExport = true;
        $this->params[self::SQL_LIMIT] = null;
        $this->params[self::SQL_OFFSET] = null;
        $data['data'] = $this->getTable();
        $data['headers'] = $this->getCsvHeaders();
        return $data;
    }
}
