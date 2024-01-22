<?php

namespace App\Services\reports;

use App\Models\ProgramBudget;
use stdClass;

class ReportBudgetComparisonService extends ReportServiceAbstract
{
    public function getReport(array $params)
    {
        $programIds = $params['selected_programs'];
        $year = $params['year'];

        $reportByProgram = [];

        foreach ($programIds as $programId) {
            $budgetData = ProgramBudget::where('program_id', $programId)
                ->whereYear('date_column', $year)
                ->orderBy('date_column', 'asc')
                ->get();

            $monthlyBudget = [];

            foreach ($budgetData as $budget) {
                $month = $budget->date_column->format('M');
                $monthlyBudget[$month] = $budget->budget_value;
            }

            $reportByProgram[$programId] = $monthlyBudget;
        }

        return $reportByProgram;
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
