<?php

namespace App\Services\reports;

use App\Models\Program;
use stdClass;

class ReportAwardSummaryService extends ReportServiceAbstract
{

    public function getTable(): array
    {
        $params = $this->params;
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

        foreach ($table as $key => $item){
            $table[$key]->program_name = $item->name;
            foreach($item->awards as $awardKey => $awardItem){
                $table[$key]->subRows[] = $awardItem;
            }
        }
        $this->table['data'] = $table;
        $this->table['total'] = $participantsReport['total'];

        return $this->table;
    }

}
