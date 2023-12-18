<?php

namespace App\Services\reports;

use App\Models\AccountType;
use App\Models\JournalEventType;
use App\Models\MediumInfo;
use App\Models\Merchant;
use App\Models\OptimalValue;
use App\Models\Program;
use App\Models\User;
use App\Services\Report\ReportServiceSumProgramAwardsPoints;
use App\Services\Report\ReportServiceSumProgramAwardsMonies;
use App\Services\Report\ReportServiceSumPostsByAccountAndJournalEventAndCredit;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Expr\Cast\Object_;
use stdClass;

class ReportPointsPurchaseService extends ReportServiceAbstract
{
    private $total = [];

    protected function calc(): array
    {
        $this->table = array ();
		// Setup the default params for the sub reports
		$subreport_params = array ();
		$subreport_params [self::DATE_BEGIN] = $this->params [self::DATE_BEGIN];
		$subreport_params [self::DATE_END] = $this->params [self::DATE_END];
		if (is_array ( $this->params [self::PROGRAMS] ) && count ( $this->params [self::PROGRAMS] ) > 0) {
			// Start by constructing the table with all of the passed in program details ordered by rank
			$all_ranked_programs = Program::read_programs ( $this->params [self::PROGRAMS], true );
			$ranked_programs = Program::read_programs ( $this->params [self::PROGRAMS], true, $this->params[self::SQL_OFFSET], $this->params[self::SQL_LIMIT]  );
			$ranked_programIds = [];
			if ($ranked_programs->isNotEmpty()) {
				foreach ( $ranked_programs as $program ) {
					array_push($ranked_programIds, $program->account_holder_id);
					$this->table [( int ) $program->account_holder_id] = $program;
					// Prime the programs report with 0's
					$this->table [( int ) $program->account_holder_id]->count = 0;
					$this->table [( int ) $program->account_holder_id]->per_participant = 0;
					$this->table [( int ) $program->account_holder_id]->avg_per_month = 0;
					$this->table [( int ) $program->account_holder_id]->avg_per_quarter = 0;
					$this->table [( int ) $program->account_holder_id]->monthly_target = 0;
					$this->table [( int ) $program->account_holder_id]->quarterly_target = 0;
					$this->table [( int ) $program->account_holder_id]->annual_target = 0;
					$this->table [( int ) $program->account_holder_id]->month_1 = 0;
					$this->table [( int ) $program->account_holder_id]->month_2 = 0;
					$this->table [( int ) $program->account_holder_id]->month_3 = 0;
					$this->table [( int ) $program->account_holder_id]->month_4 = 0;
					$this->table [( int ) $program->account_holder_id]->month_5 = 0;
					$this->table [( int ) $program->account_holder_id]->month_6 = 0;
					$this->table [( int ) $program->account_holder_id]->month_7 = 0;
					$this->table [( int ) $program->account_holder_id]->month_8 = 0;
					$this->table [( int ) $program->account_holder_id]->month_9 = 0;
					$this->table [( int ) $program->account_holder_id]->month_10 = 0;
					$this->table [( int ) $program->account_holder_id]->month_11 = 0;
					$this->table [( int ) $program->account_holder_id]->month_12 = 0;
					$this->table [( int ) $program->account_holder_id]->Q1 = 0;
					$this->table [( int ) $program->account_holder_id]->Q2 = 0;
					$this->table [( int ) $program->account_holder_id]->Q3 = 0;
					$this->table [( int ) $program->account_holder_id]->Q4 = 0;
					$this->table [( int ) $program->account_holder_id]->YTD = 0;
				}
				// unset ( $ranked_programs ); // Try to free up memory if possible
				                         // Get the Eligible Participants for each program
				// $subreport_params [self::ACCOUNT_HOLDER_IDS] = $this->params [self::PROGRAMS];
				// $subreport_params [self::PROGRAMS] = $this->params [self::PROGRAMS];
				// $subreport_params [self::DATE_BEGIN] = $this->params [self::YEAR] . "-01-01 00:00:00";
				// $subreport_params [self::DATE_END] = $this->params [self::YEAR] . "-12-31 23:59:59";
				// $subreport_params [Report_handler_count_participants_by_user_state::USER_STATES] = array (
				// 		USER_STATE_ACTIVE,
				// 		USER_STATE_LOCKED,
				// 		USER_STATE_PENDING_ACTIVATION,
				// 		USER_STATE_PENDING_DEACTIVATION 
				// );
				// $participants_report = new Report_handler_count_participants_by_user_state ( $subreport_params );
				// $participants_report_table = $participants_report->getTable ();
				// if (count ( $participants_report_table ) > 0) {
				// 	foreach ( $participants_report_table as $program_id => $participants_report_row ) {
				// 		$this->table [( int ) $program_id]->count = ( int ) $participants_report_row;
				// 		$this->table [( int ) $program_id]->annual_target = $this->params [self::TARGET] * ( int ) $participants_report_row;
				// 		$this->table [( int ) $program_id]->monthly_target = $this->table [( int ) $program_id]->annual_target / 12;
				// 		$this->table [( int ) $program_id]->quarterly_target = $this->table [( int ) $program_id]->annual_target / 4;
				// 	}
				// }
				// // Try to free up memory if possible
				// unset ( $participants_report );
				// unset ( $participants_report_table );
				// Get the monies awards
				$subreport_params [self::ACCOUNT_TYPES] = array ();
				$subreport_params [self::JOURNAL_EVENT_TYPES] = array ();
                $subreport_params [self::PROGRAMS] =  $ranked_programIds;
				$subreport_params [self::YEAR] = $this->params [self::YEAR];
				$subreport_params [self::SQL_GROUP_BY] = array (
						'p.account_holder_id',
						'atypes.name',
						'jet.type',
						self::FIELD_MONTH 
				);
				$points_report = new ReportServiceSumProgramAwardsPoints ( $subreport_params );
				$points_report_table = $points_report->getTable ();
				// Sort the points awards
				if (is_array ( $points_report_table ) && count ( $points_report_table ) > 0) {
					foreach ( $points_report_table as $program_account_holder_id => $programs_points_report_table ) {
						// Get an easier reference to the program
						$program = $this->table [$program_account_holder_id];
						if (! $program->invoice_for_awards) {
							$months = $programs_points_report_table [[self::ACCOUNT_TYPE_MONIES_AWARDED][0]] [[self::JOURNAL_EVENT_TYPES_AWARD_MONIES_TO_RECIPIENT][0]];
							if (is_array ( $months ) && count ( $months ) > 0) {
								foreach ( $months as $month => $amount ) {
									$this->table [( int ) $program->account_holder_id]->{'month_' . $month} += $amount;
									$quarter = ceil ( $month / 3 );
									$this->table [( int ) $program->account_holder_id]->{'Q' . $quarter} += $amount;
									$this->table [( int ) $program->account_holder_id]->YTD += $amount;
								}
							}
						}
					}
				}
				// Try to free up memory if possible
				unset ( $points_report );
				unset ( $points_report_table );
				// Get the points awards
				$subreport_params [self::ACCOUNT_TYPES] = array ();
				$subreport_params [self::JOURNAL_EVENT_TYPES] = array ();
                $subreport_params [self::PROGRAMS] =  $ranked_programIds;
				$subreport_params [self::YEAR] = $this->params [self::YEAR];
				$subreport_params [self::SQL_GROUP_BY] = array (
						'p.account_holder_id',
						'atypes.name',
						'jet.type',
						self::FIELD_MONTH 
				);
				$points_report = new ReportServiceSumProgramAwardsPoints ( $subreport_params );
				$points_report_table = $points_report->getTable ();
				// Sort the points awards
				if (is_array ( $points_report_table ) && count ( $points_report_table ) > 0) {
					foreach ( $points_report_table as $program_account_holder_id => $programs_points_report_table ) {
						// Get an easier reference to the program
						$program = $this->table [$program_account_holder_id];
						if ($program->invoice_for_awards) {
							$months = $programs_points_report_table [[self::ACCOUNT_TYPE_POINTS_AWARDED][0]] [[self::JOURNAL_EVENT_TYPES_AWARD_POINTS_TO_RECIPIENT][0]];
							if (is_array ( $months ) && count ( $months ) > 0) {
								foreach ( $months as $month => $amount ) {
									$this->table [( int ) $program->account_holder_id]->{'month_' . $month} += $amount;
									$quarter = ceil ( $month / 3 );
									$this->table [( int ) $program->account_holder_id]->{'Q' . $quarter} += $amount;
									$this->table [( int ) $program->account_holder_id]->YTD += $amount;
								}
							}
						}
					}
				}
				// Try to free up memory if possible
				unset ( $points_report );
				unset ( $points_report_table );
				// Get Reclaims
				// Get all types of fees, etc where we are interested in them being credits, fees from both award types are the transaction fees, they will be grouped by type, so we can pick which one we want
				$subreport_params [self::ACCOUNT_HOLDER_IDS] = $ranked_programIds;
				$subreport_params [self::PROGRAMS] = $ranked_programIds;
				$subreport_params [ReportServiceSumPostsByAccountAndJournalEventAndCredit::IS_CREDIT] = 1;
				$subreport_params [self::YEAR] = $this->params [self::YEAR];
				$subreport_params [self::SQL_GROUP_BY] = array (
						'a.account_holder_id',
						'atypes.id',
						'jet.id',
						self::FIELD_MONTH 
				);
				$subreport_params [self::ACCOUNT_TYPES] = array (
						[self::ACCOUNT_TYPE_MONIES_DUE_TO_OWNER],
						[self::ACCOUNT_TYPE_MONIES_AVAILABLE] 
				);
				$subreport_params [self::JOURNAL_EVENT_TYPES] = array (
						[self::JOURNAL_EVENT_TYPES_RECLAIM_POINTS],
						[self::JOURNAL_EVENT_TYPES_RECLAIM_MONIES] 
				);
				$credits_report = new ReportServiceSumPostsByAccountAndJournalEventAndCredit ( $subreport_params );
				$credits_report_table = $credits_report->getTable ();
				// Sort the reclaims
				if (is_array ( $credits_report_table ) && count ( $credits_report_table ) > 0) {
					foreach ( $credits_report_table as $program_account_holder_id => $programs_credits_report_table ) {
						// Get an easier reference to the program
						$program = $this->table [$program_account_holder_id];
						if (is_array ( $programs_credits_report_table ) && count ( $programs_credits_report_table ) > 0) {
							foreach ( $programs_credits_report_table as $account_type_name => $account ) {
								if (is_array ( $account ) && count ( $account ) > 0) {
									foreach ( $account as $journal_event_type => $months ) {
										if (is_array ( $months ) && count ( $months ) > 0) {
											switch ($account_type_name) {
												case [self::ACCOUNT_TYPE_MONIES_DUE_TO_OWNER] :
													switch ($journal_event_type) {
														case [self::JOURNAL_EVENT_TYPES_RECLAIM_POINTS] :
															foreach ( $months as $month => $amount ) {
																$this->table [( int ) $program->account_holder_id]->{'month_' . $month} -= $amount;
																$quarter = ceil ( $month / 3 );
																$this->table [( int ) $program->account_holder_id]->{'Q' . $quarter} -= $amount;
																$this->table [( int ) $program->account_holder_id]->YTD -= $amount;
															}
															break;
													}
													break;
												case [self::ACCOUNT_TYPE_MONIES_AVAILABLE] :
													switch ($journal_event_type) {
														case [self::JOURNAL_EVENT_TYPES_RECLAIM_MONIES] :
															foreach ( $months as $month => $amount ) {
																$this->table [( int ) $program->account_holder_id]->{'month_' . $month} -= $amount;
																$quarter = ceil ( $month / 3 );
																$this->table [( int ) $program->account_holder_id]->{'Q' . $quarter} -= $amount;
																$this->table [( int ) $program->account_holder_id]->YTD -= $amount;
															}
															break;
													}
											}
										}
									}
								}
							}
						}
					}
				}
				// Try to free up memory if possible
				unset ( $credits_report );
				unset ( $credits_report_table );
				// Calc Averages
				$month = 12;
				$quarter = 4;
				// If the report is being pulled for the current year, use the current month/quarter for the averages so they aren't skewed.
				if (date ( 'Y' ) == $this->params [self::YEAR]) {
					$month = date ( 'm' );
					$quarter = ceil ( $month / 3 );
				}
				foreach ( $this->table as $program_id => $program ) {
					if ($this->table [$program_id]->count > 0) {
						$this->table [$program_id]->per_participant = $this->table [$program_id]->YTD / $this->table [$program_id]->count;
					}
					$this->table [$program_id]->avg_per_month = $this->table [$program_id]->YTD / $month;
					$this->table [$program_id]->avg_per_quarter = $this->table [$program_id]->YTD / $quarter;
				}
			}
		}

        // $this->table['total'] =  $query->limit(9999999999)->offset(0)->count();
        $temp_array = array();
        if (is_array ( $this->table ) && count ( $this->table ) > 0) {
            foreach ( $this->table as &$row ) {
                array_push($temp_array, $row);
            }
        }
        $this->table['data'] = $temp_array;
        $this->table['total'] = count($all_ranked_programs);
        return $this->table;
    }

    public function getCsvHeaders(): array
    {
        return [
            [
                'label' => 'Program',
                'key' => 'name'
            ],
            [
                'label' => 'Jan',
                'key' => 'month_1'
            ],
            [
                'label' => 'Feb',
                'key' => 'month_2'
            ],
            [
                'label' => 'Mar',
                'key' => 'month_3'
            ],
            [
                'label' => 'Apr',
                'key' => 'month_4'
            ],
            [
                'label' => 'May',
                'key' => 'month_5'
            ],
            [
                'label' => 'Jun',
                'key' => 'month_6'
            ],
            [
                'label' => 'Jul',
                'key' => 'month_7'
            ],
            [
                'label' => 'Aug',
                'key' => 'month_8'
            ],
            [
                'label' => 'Sep',
                'key' => 'month_9'
            ],
            [
                'label' => 'Oct',
                'key' => 'month_10'
            ],
            [
                'label' => 'Nov',
                'key' => 'month_11'
            ],
            [
                'label' => 'Dec',
                'key' => 'month_12'
            ],
        ];
    }

    protected function getReportForCSV(): array
    {
        $this->isExport = true;
        $data = $this->getTable();

        $data['headers'] = $this->getCsvHeaders();
        return $data;
    }

}
