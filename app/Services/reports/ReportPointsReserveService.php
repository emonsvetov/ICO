<?php

namespace App\Services\reports;

use App\Models\AccountType;
use App\Models\JournalEventType;
use App\Models\MediumInfo;
use App\Models\Merchant;
use App\Models\OptimalValue;
use App\Models\Program;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Expr\Cast\Object_;
use stdClass;
use App\Services\Reports\ReportSumPostsByAccountAndJournalEventAndCreditService;

class ReportPointsReserveService extends ReportServiceAbstract
{
    private $total = [];

    protected function calc(): array
    {
        $this->table = array ();
		// Setup the default params for the sub reports
		$subreport_params = array ();
		$subreport_params[self::DATE_BEGIN] = $this->params[self::DATE_BEGIN];
		$subreport_params[self::DATE_END] = $this->params[self::DATE_END];
        $total_programs = Program::read_programs ( $this->params [self::PROGRAMS], false );
        $ranked_programs = Program::read_programs ( $this->params [self::PROGRAMS], false, $this->params[self::SQL_OFFSET], $this->params[self::SQL_LIMIT]  );
        $subreport_params [self::ACCOUNT_HOLDER_IDS] = array ();

        if ($ranked_programs->isNotEmpty()) {
            foreach ( $ranked_programs as $program ) {
           
                $subreport_params [self::ACCOUNT_HOLDER_IDS] [] = ( int ) $program->account_holder_id;
				$this->table [( int ) $program->account_holder_id] = $program;
				// Prime the programs report with 0's
				$this->table [( int ) $program->account_holder_id]->value_awarded = 0;
				$this->table [( int ) $program->account_holder_id]->redeemed = 0;
				$this->table [( int ) $program->account_holder_id]->expired = 0;
				$this->table [( int ) $program->account_holder_id]->amount_due = 0;
				$this->table [( int ) $program->account_holder_id]->value_unredeemed = 0;
				$this->table [( int ) $program->account_holder_id]->value_paid = 0;
				$this->table [( int ) $program->account_holder_id]->calculated_reserve = 0;
				$this->table [( int ) $program->account_holder_id]->reclaimed = 0;
				$this->table [( int ) $program->account_holder_id]->balance = 0;
            }
        }
        $subreport_params [self::PROGRAMS] = $subreport_params [self::ACCOUNT_HOLDER_IDS];
        $subreport_params [ReportSumPostsByAccountAndJournalEventAndCreditService::IS_CREDIT] = 1;
        $subreport_params [self::ACCOUNT_TYPES] = array (
            [self::ACCOUNT_TYPE_MONIES_DUE_TO_OWNER],
            [self::ACCOUNT_TYPE_MONIES_AVAILABLE],
            [self::ACCOUNT_TYPE_POINTS_REDEEMED],
            [self::ACCOUNT_TYPE_MONIES_REDEEMED],
            [self::ACCOUNT_TYPE_MONIES_EXPIRED],
            [self::ACCOUNT_TYPE_POINTS_EXPIRED] 
        );
        $subreport_params [self::JOURNAL_EVENT_TYPES] = array (
            [self::JOURNAL_EVENT_TYPES_AWARD_POINTS_TO_RECIPIENT],
            [self::JOURNAL_EVENT_TYPES_AWARD_MONIES_TO_RECIPIENT],
            [self::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_POINTS],
            [self::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_MONIES_PENDING],
            [self::JOURNAL_EVENT_TYPES_REDEEM_POINTS_FOR_GIFT_CODES],
            [self::JOURNAL_EVENT_TYPES_REDEEM_POINTS_FOR_INTERNATIONAL_SHOPPING],
            [self::JOURNAL_EVENT_TYPES_REDEEM_MONIES_FOR_GIFT_CODES],
            [self::JOURNAL_EVENT_TYPES_EXPIRE_POINTS],
            [self::JOURNAL_EVENT_TYPES_EXPIRE_MONIES],
            [self::JOURNAL_EVENT_TYPES_DEACTIVATE_POINTS],
            [self::JOURNAL_EVENT_TYPES_DEACTIVATE_MONIES],
            [self::JOURNAL_EVENT_TYPES_RECLAIM_POINTS],
            [self::JOURNAL_EVENT_TYPES_RECLAIM_MONIES]
        );
        $credits_report = new ReportSumPostsByAccountAndJournalEventAndCreditService ( $subreport_params );
        $credits_report_table = $credits_report->getTable ();
        if (is_array ( $credits_report_table ) && count ( $credits_report_table ) > 0) {
            foreach ( $credits_report_table as $program_account_holder_id => $programs_credits_report_table ) {
                // Get an easier reference to the program
                $program = $this->table [$program_account_holder_id];
                if (is_array ( $programs_credits_report_table ) && count ( $programs_credits_report_table ) > 0) {
                    foreach ( $programs_credits_report_table as $account_type_name => $account ) {
                        if (is_array ( $account ) && count ( $account ) > 0) {
                            foreach ( $account as $journal_event_type => $amount ) {
                                switch ($account_type_name) {
                                    case [self::ACCOUNT_TYPE_MONIES_DUE_TO_OWNER] :
                                        switch ($journal_event_type) {
                                            case [self::JOURNAL_EVENT_TYPES_RECLAIM_POINTS] :
                                                if ($program->invoice_for_awards) {
                                                    $this->table [( int ) $program->account_holder_id]->reclaimed += $amount;
                                                }
                                                break;
                                            case [self::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_POINTS] :
                                                if ($program->invoice_for_awards) {
                                                    $this->table [( int ) $program->account_holder_id]->value_paid += $amount;
                                                }
                                                break;
                                        }
                                        break;
                                    case [self::ACCOUNT_TYPE_MONIES_AVAILABLE] :
                                        switch ($journal_event_type) {
                                            case [self::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_MONIES_PENDING] :
                                                if (! $program->invoice_for_awards) {
                                                    $this->table [( int ) $program->account_holder_id]->value_paid += $amount;
                                                }
                                                break;
                                            case [self::JOURNAL_EVENT_TYPES_RECLAIM_MONIES] :
                                                if (! $program->invoice_for_awards) {
                                                    $this->table [( int ) $program->account_holder_id]->reclaimed += $amount;
                                                }
                                                break;
                                        }
                                        break;
                                    case [self::ACCOUNT_TYPE_MONIES_EXPIRED] :
                                        switch ($journal_event_type) {
                                            case [self::JOURNAL_EVENT_TYPES_EXPIRE_MONIES] :
                                                if (! $program->invoice_for_awards) {
                                                    $this->table [( int ) $program->account_holder_id]->expired += $amount; // Add so expire and deactive are summed
                                                }
                                                break;
                                            case [self::JOURNAL_EVENT_TYPES_DEACTIVATE_MONIES] :
                                                if (! $program->invoice_for_awards) {
                                                    $this->table [( int ) $program->account_holder_id]->expired += $amount; // Add so expire and deactive are summed
                                                }
                                                break;
                                        }
                                        break;
                                    case [self::ACCOUNT_TYPE_POINTS_EXPIRED] :
                                        switch ($journal_event_type) {
                                            case [self::JOURNAL_EVENT_TYPES_EXPIRE_POINTS] :
                                                if ($program->invoice_for_awards) {
                                                    $this->table [( int ) $program->account_holder_id]->expired += $amount; // Add so expire and deactive are summed
                                                }
                                                break;
                                            case [self::JOURNAL_EVENT_TYPES_DEACTIVATE_POINTS] :
                                                if ($program->invoice_for_awards) {
                                                    $this->table [( int ) $program->account_holder_id]->expired += $amount; // Add so expire and deactive are summed
                                                }
                                                break;
                                        }
                                        break;
                                    case [self::ACCOUNT_TYPE_MONIES_REDEEMED] :
                                        switch ($journal_event_type) {
                                            case [self::JOURNAL_EVENT_TYPES_REDEEM_MONIES_FOR_GIFT_CODES] :
                                                if (! $program->invoice_for_awards) {
                                                    $this->table [( int ) $program->account_holder_id]->redeemed += $amount;
                                                }
                                                break;
                                        }
                                        break;
                                    case [self::ACCOUNT_TYPE_POINTS_REDEEMED] :
                                        switch ($journal_event_type) {
                                            case [self::JOURNAL_EVENT_TYPES_REDEEM_POINTS_FOR_GIFT_CODES] :
                                            case [self::JOURNAL_EVENT_TYPES_REDEEM_POINTS_FOR_INTERNATIONAL_SHOPPING] :
                                                if ($program->invoice_for_awards) {
                                                    $this->table [( int ) $program->account_holder_id]->redeemed = $amount;
                                                }
                                                break;
                                        }
                                        break;
                                }
                            }
                        }
                    }
                }
            }
        }
        // Get all of the payment reversals
        $subreport_params [ReportSumPostsByAccountAndJournalEventAndCreditService::IS_CREDIT] = 0;
        $subreport_params [self::ACCOUNT_TYPES] = array (
            [self::ACCOUNT_TYPE_MONIES_AVAILABLE],
            [self::ACCOUNT_TYPE_MONIES_DUE_TO_OWNER] 
        );
        $subreport_params [self::JOURNAL_EVENT_TYPES] = array (
            [self::JOURNAL_EVENT_TYPES_REVERSAL_PROGRAM_PAYS_FOR_POINTS],
            [self::JOURNAL_EVENT_TYPES_REVERSAL_PROGRAM_PAYS_FOR_MONIES_PENDING] 
        );
        $debits_report = new ReportSumPostsByAccountAndJournalEventAndCreditService ( $subreport_params );
        $debits_report_table = $debits_report->getTable ();
        if (is_array ( $debits_report_table ) && count ( $debits_report_table ) > 0) {
            foreach ( $debits_report_table as $program_account_holder_id => $programs_debits_report_table ) {
                // Get an easier reference to the program
                $program = $this->table [$program_account_holder_id];
                if (is_array ( $programs_debits_report_table ) && count ( $programs_debits_report_table ) > 0) {
                    foreach ( $programs_debits_report_table as $account_type_name => $account ) {
                        if (is_array ( $account ) && count ( $account ) > 0) {
                            foreach ( $account as $journal_event_type => $amount ) {
                                switch ($account_type_name) {
                                    case [self::ACCOUNT_TYPE_MONIES_AVAILABLE] :
                                        switch ($journal_event_type) {
                                            case [self::JOURNAL_EVENT_TYPES_REVERSAL_PROGRAM_PAYS_FOR_MONIES_PENDING] :
                                                if (! $program->invoice_for_awards) {
                                                    $this->table [( int ) $program->account_holder_id]->value_paid -= $amount;
                                                }
                                                break;
                                        }
                                        break;
                                    case [self::ACCOUNT_TYPE_MONIES_DUE_TO_OWNER] :
                                        switch ($journal_event_type) {
                                            case [self::JOURNAL_EVENT_TYPES_REVERSAL_PROGRAM_PAYS_FOR_POINTS] :
                                                if ($program->invoice_for_awards) {
                                                    $this->table [( int ) $program->account_holder_id]->value_paid -= $amount;
                                                }
                                                break;
                                        }
                                        break;
                                }
                            }
                        }
                    }
                }
            }
        }
        	// Get the monies awards
        $subreport_params [self::ACCOUNT_TYPES] = array ();
        $subreport_params [self::JOURNAL_EVENT_TYPES] = array ();
        $points_report = new ReportSumProgramAwardsMoniesService ( $subreport_params );
        $points_report_table = $points_report->getTable ();
        // Sort the points awards
        if (is_array ( $points_report_table ) && count ( $points_report_table ) > 0) {
            foreach ( $points_report_table as $program_account_holder_id => $programs_points_report_table ) {
                // Get an easier reference to the program
                $program = $this->table [$program_account_holder_id];
                if (! $program->invoice_for_awards) {
                    $this->table [( int ) $program->account_holder_id]->value_awarded += $programs_points_report_table [ACCOUNT_TYPE_MONIES_AWARDED] [JOURNAL_EVENT_TYPES_AWARD_MONIES_TO_RECIPIENT];
                }
            }
        }
        // Get the points awards
        $subreport_params [self::ACCOUNT_TYPES] = array ();
        $subreport_params [self::JOURNAL_EVENT_TYPES] = array ();
        $points_report = new ReportSumProgramAwardsPointsService ( $subreport_params );
        $points_report_table = $points_report->getTable ();
        // Sort the points awards
        if (is_array ( $points_report_table ) && count ( $points_report_table ) > 0) {
            foreach ( $points_report_table as $program_account_holder_id => $programs_points_report_table ) {
                // Get an easier reference to the program
                $program = $this->table [$program_account_holder_id];
                if ($program->invoice_for_awards) {
                    $this->table [( int ) $program->account_holder_id]->value_awarded += $programs_points_report_table [[self::ACCOUNT_TYPE_POINTS_AWARDED][0]] [[self::JOURNAL_EVENT_TYPES_AWARD_POINTS_TO_RECIPIENT][0]];
                }
            }
        }
        $temp_array = array();
        if (is_array ( $this->table ) && count ( $this->table ) > 0) {
			foreach ( $this->table as &$row ) {
				$row->value_unredeemed = $row->value_awarded - $row->expired - $row->reclaimed - $row->redeemed;
				$row->balance = $row->value_awarded - $row->reclaimed - $row->value_paid;
				if (isset ( $row->reserve_percentage ) && $row->reserve_percentage > 0) {
					/*
					 * if ($row->invoice_for_awards)
					 * {
					 */
					$row->calculated_reserve = $row->value_unredeemed * ($row->reserve_percentage / 100);
					/*
					 * } else {
					 * $row->calculated_reserve = ($row->value_paid - $row->expired - $row->redeemed) * ($row->reserve_percentage / 100);
					 * }
					 */
				}
                else {
                    $row->reserve_percentage = 0;
                }

                array_push($temp_array, $row);
			}
		}
        $this->table = [];
        $this->table['data'] = $temp_array;
        $this->table['total'] = count($total_programs);
        return  $this->table;
    }
    

    public function getCsvHeaders(): array
    {
        return [
            [
                'label' => 'Program Name',
                'key' => 'program_name'
            ],
            [
                'label' => 'Status',
                'key' => 'status'
            ],
            [
                'label' => 'count',
                'key' => 'count'
            ],
            [
                'label' => 'Unique Count',
                'key' => 'unique_count'
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
