<?php

namespace App\Services\reports;

use App\Models\Program;
use stdClass;

class ReportAwardSummaryService extends ReportServiceAbstract
{

    public function getTable(): array
    {
        $params = $this->params;
        $params[self::SQL_LIMIT] = null;
        $params[self::SQL_OFFSET] = null;
        $table = [];
        $className = ReportAwardSummaryParticipantsService::class;
        $participantsReport = (new $className($params))->getReport();

        $programs = Program::whereIn('account_holder_id', $this->params[self::PROGRAMS])->get()->toArray();
        foreach ($programs as $program) {
            $program = (object)$program;
            $table[( int )$program->account_holder_id] = $program;
            $table[( int )$program->account_holder_id]->awards = [];
        }

        // Use the participants to prime the report with 0 data, and to gather the participant id's we need to use in the awards and reclaims sub reports
        if (isset ($participantsReport['data']) && count($participantsReport['data']) > 0) {
            foreach ($participantsReport['data'] as $participantsReportRow) {
                $participantAwardRow = new stdClass();
                for ($i = 1; $i <= 12; $i++) {
                    $columnName = "month{$i}_value";
                    $participantAwardRow->$columnName = 0;
                    $columnName2 = "month{$i}_count";
                    $participantAwardRow->$columnName2 = 0;
                }
                $participantAwardRow->recipientFirstName = $participantsReportRow->first_name;
                $participantAwardRow->recipientLastName = $participantsReportRow->last_name;
                $participantAwardRow->year = $params[self::YEAR];
                $table[( int )$participantsReportRow->program_id]->awards[$participantsReportRow->user_id] = $participantAwardRow;
            }
        }

        // We can't use the offset of limit since the results will differ from the awards. Hopefully there aren't too may of these records
        unset($params [self::SQL_LIMIT]);
        unset($params [self::SQL_OFFSET]);
        $rowsToTotal = [];
        for ($i = 1; $i <= 12; $i++) {
            $columnName = "month{$i}_value";
            $columnName2 = "month{$i}_count";
            $rowsToTotal[] = $columnName;
            $rowsToTotal[] = $columnName2;
        }
        $className = ReportAwardSummaryAwardsService::class;
        $awardsReport = (new $className($params))->getReport();

        if (isset ($awardsReport['data']) && count($awardsReport['data']) > 0) {
            foreach ($awardsReport['data'] as $awardsReportRow) {
                if (isset ($table[( int )$awardsReportRow->program_id]->awards[$awardsReportRow->recipient_id])) {
                    foreach ($awardsReportRow as $key => $value) {
                        if (!in_array($key, $rowsToTotal)) {
                            continue;
                        }
                        $table[( int )$awardsReportRow->program_id]->awards[$awardsReportRow->recipient_id]->$key += $value;
                    }
                }
            }
        }

        $className = ReportAwardSummaryReclaimsService::class;
        $reclaimedReport = (new $className($params))->getReport();

        if (isset ($reclaimedReport['data']) && count($reclaimedReport['data']) > 0) {
            foreach ($reclaimedReport['data'] as $reclaimedReportRow) {
                // If we have a row for the participant already, then we just need to subtract out the reclaimed values
                if (isset ($table[( int )$reclaimedReportRow->program_id]->awards[$reclaimedReportRow->recipient_id])) {
                    foreach ($reclaimedReportRow as $key => $value) {
                        if (!in_array($key, $rowsToTotal)) {
                            continue;
                        }
                        $table[( int )$reclaimedReportRow->program_id]->awards[$reclaimedReportRow->recipient_id]->$key -= $value;
                    }
                }
            }
        }

        $total = 0;
        foreach ($table as $key => $item) {
            foreach ($item->awards as $awardKey => $awardItem) {
                $total++;
            }
        }

        $break = false;
        $offset = 0;
        $count = 0;
        foreach ($table as $key => $item) {
            if ($break){
                unset($table[$key]);
                continue;
            }
            $table[$key]->program_name = $item->name;
            foreach ($item->awards as $awardKey => $awardItem) {
                $offset++;
                if ($offset <= $this->params[self::SQL_OFFSET]) {
                    continue;
                }
                $table[$key]->subRows[] = $awardItem;
                $count++;
                if ($count>= $this->params[self::SQL_LIMIT]){
                    $break = true;
                    break;
                }
            }
        }
        foreach ($table as $key => $item) {
            if(empty($item->subRows)){
                unset($table[$key]);
            }
        }

        $this->table['data'] = $table;
        $this->table['total'] = $total;

        return $this->table;
    }

    protected function getReportForCSV(): array
    {
        $this->isExport = true;
        $this->params[self::SQL_LIMIT] = null;
        $this->params[self::SQL_OFFSET] = null;
        $table = $this->getTable();
        $data = [];
        if (isset($table['data'])) {
            foreach ($table['data'] as $key => $item) {
                foreach ($item->awards as $awardKey => $awardItem) {
                    $awardItem->program_name = $item->name;
                    $data[] = $awardItem;
                }
            }
        }
        $data['data'] = $data;
        $data['total'] = count($data);
        $data['headers'] = $this->getCsvHeaders();
        return $data;
    }

    public function getCsvHeaders(): array
    {
        return [
            [
                'label' => 'Program Name',
                'key' => 'program_name'
            ],
            [
                'label' => 'Last Name',
                'key' => 'recipientLastName'
            ],
            [
                'label' => 'First Name',
                'key' => 'recipientFirstName'
            ],
            [
                'label' => 'Jan',
                'key' => 'month1_value'
            ],
            [
                'label' => 'Feb',
                'key' => 'month2_value'
            ],
            [
                'label' => 'Mar',
                'key' => 'month3_value'
            ],
            [
                'label' => 'Apr',
                'key' => 'month4_value'
            ],
            [
                'label' => 'May',
                'key' => 'month5_value'
            ],
            [
                'label' => 'Jun',
                'key' => 'month6_value'
            ],
            [
                'label' => 'Jul',
                'key' => 'month7_value'
            ],
            [
                'label' => 'Aug',
                'key' => 'month8_value'
            ],
            [
                'label' => 'Sep',
                'key' => 'month9_value'
            ],
            [
                'label' => 'Oct',
                'key' => 'month10_value'
            ],
            [
                'label' => 'Nov',
                'key' => 'month11_value'
            ],
            [
                'label' => 'Dec',
                'key' => 'month12_value'
            ],
        ];
    }

}
