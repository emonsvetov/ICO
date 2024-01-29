<?php

namespace App\Services\reports;

use App\Models\AccountType;
use App\Models\JournalEventType;
use App\Models\Program;
use App\Services\UserService;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Program Status Report for frontend (ico_v3_react_program)
 */
class ReportProgramStatusService extends ReportServiceAbstract
{

    protected function calc(): array
    {
        $table = [];
        $this->table = [];

        $dateBegin = date('Y-m-d 00:00:00', strtotime($this->params[self::DATE_BEGIN]));
        $dateBegin2000 = '2000-01-01 00:00:00';
        $dateEnd = date('Y-m-d 23:59:59', strtotime($this->params[self::DATE_END]));
        $programIds = $this->params[self::PROGRAMS];

        $programs = Program::getFlatTree();
        foreach ($programs as $program) {
            if (!in_array($program->account_holder_id, $programIds)){
                continue;
            }
            $program = (object)$program->toArray();

            $table[$program->account_holder_id] = $program;
            $table[$program->account_holder_id]->program_name = $program->name;

            $table[$program->account_holder_id]->participants_count = 0;
            $table[$program->account_holder_id]->new_participants_count = 0;
            $table[$program->account_holder_id]->awards_count = 0;
            $table[$program->account_holder_id]->awards_value = 0;
            $table[$program->account_holder_id]->transaction_fees = 0;
            $table[$program->account_holder_id]->ytd_awards_count = 0;
            $table[$program->account_holder_id]->ytd_awards_value = 0;
            $table[$program->account_holder_id]->ytd_transaction_fees = 0;
            $table[$program->account_holder_id]->mtd_awards_count = 0;
            $table[$program->account_holder_id]->mtd_awards_value = 0;
            $table[$program->account_holder_id]->mtd_transaction_fees = 0;
        }

        $userStatuses = [
            config('global.user_status_active'),
            config('global.user_status_pending_activation'),
            config('global.user_status_locked'),
            config('global.user_status_pending_deactivation'),
            config('global.user_status_new'),
        ];
        $countParticipants = $this->reportHelper->countParticipantsByUserStatuses($userStatuses, $dateBegin2000, $dateEnd);
        foreach ($countParticipants as $program_id => $participant_count) {
            if (isset($table[$program_id])){
                $table[$program_id]->participants_count = $participant_count;
            }
        }

        $args = [];
        $args['total'] = true;
        $awardsAudit = $this->reportHelper->awardsAudit($programIds, $dateBegin, $dateEnd, $args);
        foreach ($awardsAudit as $item ) {
            if ($item->account_holder_id && isset($table[$item->account_holder_id])){
                $table[$item->account_holder_id]->awards_value = $item->total;
                $table[$item->account_holder_id]->awards_count = $item->count;
            }
        }

        $args = [];
        $args['total'] = true;
        $reclaimsAudit = $this->reportHelper->reclaimsAudit($programIds, $dateBegin, $dateEnd, $args);
        foreach ($reclaimsAudit as $item ) {
            if ($item->account_holder_id && isset($table[$item->account_holder_id])){
                $table[$item->account_holder_id]->awards_count -= $item->count;
            }
        }

        $args = [];
        $args['accountTypes'] = [
            AccountType::ACCOUNT_TYPE_MONIES_FEES,
            AccountType::ACCOUNT_TYPE_MONIES_DUE_TO_OWNER,
            AccountType::ACCOUNT_TYPE_MONIES_AVAILABLE
        ];
        $args['journalEventTypes'] = [
            JournalEventType::JOURNAL_EVENT_TYPES_AWARD_POINTS_TO_RECIPIENT,
            JournalEventType::JOURNAL_EVENT_TYPES_AWARD_MONIES_TO_RECIPIENT
        ];
        $args['isCredit'] = true;
        $credits_report = $this->reportHelper->sumPostsByAccountAndJournalEventAndCredit($dateBegin, $dateEnd, $args);

        foreach ($credits_report as $program_account_holder_id => $programs_credits_report_table) {
            if (!in_array($program_account_holder_id, $programIds)){
                continue;
            }
            $program = $table[$program_account_holder_id];
            if (is_array($programs_credits_report_table) && count($programs_credits_report_table) > 0) {
                foreach ($programs_credits_report_table as $account_type_name => $account) {
                    if (is_array($account) && count($account) > 0) {
                        foreach ($account as $journal_event_type => $amount) {
                            switch ($account_type_name) {
                                case AccountType::ACCOUNT_TYPE_MONIES_FEES :
                                    switch ($journal_event_type) {
                                        case JournalEventType::JOURNAL_EVENT_TYPES_AWARD_POINTS_TO_RECIPIENT :
                                        case JournalEventType::JOURNAL_EVENT_TYPES_AWARD_MONIES_TO_RECIPIENT :
                                            $table[$program->account_holder_id]->transaction_fees += $amount;
                                            break;
                                    }
                                    break;
                                case AccountType::ACCOUNT_TYPE_MONIES_DUE_TO_OWNER :
                                    switch ($journal_event_type) {
                                        case JournalEventType::JOURNAL_EVENT_TYPES_AWARD_POINTS_TO_RECIPIENT :
                                            $table[$program->account_holder_id]->awards_value -= $amount;
                                            break;
                                    }
                                    break;
                                case AccountType::ACCOUNT_TYPE_MONIES_AVAILABLE :
                                    switch ($journal_event_type) {
                                        case JournalEventType::JOURNAL_EVENT_TYPES_AWARD_MONIES_TO_RECIPIENT :
                                            $table[$program->account_holder_id]->awards_value -= $amount;
                                            break;
                                    }
                                    break;
                            }
                        }
                    }
                }
            }
        }

        /** MTD, month report  */
        $userStatuses = [
            config('global.user_status_new'),
        ];
        $countParticipants = $this->reportHelper->countParticipantsByUserStatuses($userStatuses, $dateBegin2000, $dateEnd);
        foreach ($countParticipants as $program_id => $participant_count) {
            if (!in_array($program_id, $programIds)){
                continue;
            }
            $table[$program_id]->new_participants_count = $participant_count;
        }

        $month = date('m', strtotime($dateEnd));
        $dateBeginMonth = date('Y', strtotime($dateEnd)) . '-' . $month . '-01 00:00:00';
        $args = [];
        $args['total'] = true;
        $awardsAudit = $this->reportHelper->awardsAudit($programIds, $dateBeginMonth, $dateEnd, $args);
        foreach ($awardsAudit as $item ) {
            if ($item->account_holder_id && isset($table[$item->account_holder_id])){
                $table[$item->account_holder_id]->mtd_awards_value = $item->total;
                $table[$item->account_holder_id]->mtd_awards_count = $item->count;
            }
        }

        $args = [];
        $args['total'] = true;
        $reclaimsAudit = $this->reportHelper->reclaimsAudit($programIds, $dateBeginMonth, $dateEnd, $args);
        foreach ($reclaimsAudit as $item ) {
            if ($item->account_holder_id && isset($table[$item->account_holder_id])){
                $table[$item->account_holder_id]->mtd_awards_count -= $item->count;
            }
        }

        $args = [];
        $args['accountTypes'] = [
            AccountType::ACCOUNT_TYPE_MONIES_FEES,
            AccountType::ACCOUNT_TYPE_MONIES_DUE_TO_OWNER,
            AccountType::ACCOUNT_TYPE_MONIES_AVAILABLE
        ];
        $args['journalEventTypes'] = [
            JournalEventType::JOURNAL_EVENT_TYPES_AWARD_POINTS_TO_RECIPIENT,
            JournalEventType::JOURNAL_EVENT_TYPES_AWARD_MONIES_TO_RECIPIENT
        ];
        $args['isCredit'] = true;
        $credits_report = $this->reportHelper->sumPostsByAccountAndJournalEventAndCredit($dateBeginMonth, $dateEnd, $args);

        foreach ($credits_report as $program_account_holder_id => $programs_credits_report_table) {
            if (!in_array($program_account_holder_id, $programIds)){
                continue;
            }
            $program = $table[$program_account_holder_id];
            if (is_array($programs_credits_report_table) && count($programs_credits_report_table) > 0) {
                foreach ($programs_credits_report_table as $account_type_name => $account) {
                    if (is_array($account) && count($account) > 0) {
                        foreach ($account as $journal_event_type => $amount) {
                            switch ($account_type_name) {
                                case AccountType::ACCOUNT_TYPE_MONIES_FEES :
                                    switch ($journal_event_type) {
                                        case JournalEventType::JOURNAL_EVENT_TYPES_AWARD_POINTS_TO_RECIPIENT :
                                        case JournalEventType::JOURNAL_EVENT_TYPES_AWARD_MONIES_TO_RECIPIENT :
                                            $table[$program->account_holder_id]->mtd_transaction_fees += $amount;
                                            break;
                                    }
                                    break;
                                case AccountType::ACCOUNT_TYPE_MONIES_DUE_TO_OWNER :
                                    switch ($journal_event_type) {
                                        case JournalEventType::JOURNAL_EVENT_TYPES_AWARD_POINTS_TO_RECIPIENT :
                                            $table[$program->account_holder_id]->mtd_awards_value -= $amount;
                                            break;
                                    }
                                    break;
                                case AccountType::ACCOUNT_TYPE_MONIES_AVAILABLE :
                                    switch ($journal_event_type) {
                                        case JournalEventType::JOURNAL_EVENT_TYPES_AWARD_MONIES_TO_RECIPIENT :
                                            $table[$program->account_holder_id]->mtd_awards_value -= $amount;
                                            break;
                                    }
                                    break;
                            }
                        }
                    }
                }
            }
        }

        /** YTD, year report  */
        $dateBeginYear = date('Y', strtotime($dateEnd)) . '-01-01 00:00:00';
        $args = [];
        $args['total'] = true;
        $awardsAudit = $this->reportHelper->awardsAudit($programIds, $dateBeginYear, $dateEnd, $args);
        foreach ($awardsAudit as $item ) {
            if ($item->account_holder_id && isset($table[$item->account_holder_id])){
                $table[$item->account_holder_id]->ytd_awards_value = $item->total;
                $table[$item->account_holder_id]->ytd_awards_count = $item->count;
            }
        }

        $args = [];
        $args['total'] = true;
        $reclaimsAudit = $this->reportHelper->reclaimsAudit($programIds, $dateBeginYear, $dateEnd, $args);
        foreach ($reclaimsAudit as $item ) {
            if ($item->account_holder_id && isset($table[$item->account_holder_id])){
                $table[$item->account_holder_id]->ytd_awards_count -= $item->count;
            }
        }

        $args = [];
        $args['accountTypes'] = [
            AccountType::ACCOUNT_TYPE_MONIES_FEES,
            AccountType::ACCOUNT_TYPE_MONIES_DUE_TO_OWNER,
            AccountType::ACCOUNT_TYPE_MONIES_AVAILABLE
        ];
        $args['journalEventTypes'] = [
            JournalEventType::JOURNAL_EVENT_TYPES_AWARD_POINTS_TO_RECIPIENT,
            JournalEventType::JOURNAL_EVENT_TYPES_AWARD_MONIES_TO_RECIPIENT
        ];
        $args['isCredit'] = true;
        $credits_report = $this->reportHelper->sumPostsByAccountAndJournalEventAndCredit($dateBeginYear, $dateEnd, $args);

        foreach ($credits_report as $program_account_holder_id => $programs_credits_report_table) {
            if (!in_array($program_account_holder_id, $programIds)){
                continue;
            }
            $program = $table[$program_account_holder_id];
            if (is_array($programs_credits_report_table) && count($programs_credits_report_table) > 0) {
                foreach ($programs_credits_report_table as $account_type_name => $account) {
                    if (is_array($account) && count($account) > 0) {
                        foreach ($account as $journal_event_type => $amount) {
                            switch ($account_type_name) {
                                case AccountType::ACCOUNT_TYPE_MONIES_FEES :
                                    switch ($journal_event_type) {
                                        case JournalEventType::JOURNAL_EVENT_TYPES_AWARD_POINTS_TO_RECIPIENT :
                                        case JournalEventType::JOURNAL_EVENT_TYPES_AWARD_MONIES_TO_RECIPIENT :
                                            $table[$program->account_holder_id]->ytd_transaction_fees += $amount;
                                            break;
                                    }
                                    break;
                                case AccountType::ACCOUNT_TYPE_MONIES_DUE_TO_OWNER :
                                    switch ($journal_event_type) {
                                        case JournalEventType::JOURNAL_EVENT_TYPES_AWARD_POINTS_TO_RECIPIENT :
                                            $table[$program->account_holder_id]->ytd_awards_value -= $amount;
                                            break;
                                    }
                                    break;
                                case AccountType::ACCOUNT_TYPE_MONIES_AVAILABLE :
                                    switch ($journal_event_type) {
                                        case JournalEventType::JOURNAL_EVENT_TYPES_AWARD_MONIES_TO_RECIPIENT :
                                            $table[$program->account_holder_id]->ytd_awards_value -= $amount;
                                            break;
                                    }
                                    break;
                            }
                        }
                    }
                }
            }
        }



        foreach ($table as $data) {
            if ($data->depth != 0){
                $pathArr = explode('.', $data->path);
                foreach ($pathArr as $item)
                {
                    if ($item != $data->id){
                        $tableKey = array_search($item, array_map(function($v){return $v->id;},$table));
                        if (!$tableKey){
                            continue;
                        }
                        $table[$tableKey]->participants_count += $data->participants_count;
                        $table[$tableKey]->new_participants_count += $data->new_participants_count;
                        $table[$tableKey]->awards_count += $data->awards_count;
                        $table[$tableKey]->awards_value += $data->awards_value;
                        $table[$tableKey]->ytd_awards_count += $data->ytd_awards_count;
                        $table[$tableKey]->ytd_awards_value += $data->ytd_awards_value;
                        $table[$tableKey]->mtd_awards_count += $data->mtd_awards_count;
                        $table[$tableKey]->mtd_awards_value += $data->mtd_awards_value;
                    }
                }
            }
        }

        foreach ($table as $key => $data) {
            $data->transaction_fees = self::averageFormat($data->awards_value, $data->awards_count);
            $data->ytd_transaction_fees = self::averageFormat($data->ytd_awards_value, $data->ytd_awards_count);
            $data->mtd_transaction_fees = self::averageFormat($data->mtd_awards_value, $data->mtd_awards_count);
            $table[$key] = $data;
        }

        $this->table['data']['report'] = array_values($table);
        $this->table['total'] = count($table);

        return $this->table;
    }

    public static function averageFormat($value, $count)
    {
        $average = (int) $count ? $value / $count : 0;
        return (int) $average == $average ? $average : number_format($average, 2, '.', '');
    }

    public function getCsvHeaders(): array
    {
        return [
            [
                'label' => 'Program Name',
                'key' => 'program_name'
            ],
            [
                'label' => "Total Participants",
                'key' => "participants_count",
            ],
            [
                'label'=> "New Participants",
                'key'=> "new_participants_count",
            ],
            [
                'label'=> "Awards",
                'key'=> "awards_count",
            ],
            [
                'label'=> "Value",
                'key'=> "awards_value",
            ],
            [
                'label'=> "Average",
                'key'=> "transaction_fees",
            ],
            [
                'label'=> "MTD Awards",
                'key'=> "mtd_awards_count",
            ],
            [
                'label'=> "MTD Value",
                'key'=> "mtd_awards_value",
            ],
            [
                'label'=> "MTD Average",
                'key'=> "mtd_transaction_fees",
            ],
            [
                'label'=> "YTD Awards",
                'key'=> "ytd_awards_count",
            ],
            [
                'label'=> "YTD Value",
                'key'=> "ytd_awards_value",
            ],
            [
                'label'=> "YTD Average",
                'key'=> "ytd_transaction_fees",
            ],
        ];
    }


}
