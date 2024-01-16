<?php

namespace App\Services\reports;

use App\Models\AccountType;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use stdClass;

class ReportAnnualAwardsSummaryService extends ReportServiceAbstract
{

    const ROW_AWARD_TOTALS = 'event_summary_points_awarded';

	const ROW_EVENT_SUMMARY = 'event_summary_program_reward';

	const ROW_BUDGET = 'event_summary_program_budget';

	const ROW_RECLAIMED = 'event_summary_program_reclaimed';

	const ROW_TRANSACTION_FEES = 'event_summary_transaction_fees';

    const ACCOUNT_TYPE_MONIES_DUE_TO_OWNER = "Monies Due to Owner";

    const ACCOUNT_TYPE_MONIES_AVAILABLE = "Monies Available";

    const JOURNAL_EVENT_TYPES_RECLAIM_POINTS = "Reclaim points";

    const JOURNAL_EVENT_TYPES_RECLAIM_MONIES = "Reclaim monies";

    const JOURNAL_EVENT_TYPES_AWARD_POINTS_TO_RECIPIENT = 'Award points to recipient';

    const JOURNAL_EVENT_TYPES_AWARD_MONIES_TO_RECIPIENT = "Award monies to recipient";

    const ACCOUNT_TYPE_MONIES_FEES = "Monies Fees";


   public function getTable(): array
    {
        $this->table = array ();
		$this->table [self::ROW_AWARD_TOTALS] = new stdClass ();
		$this->table [self::ROW_BUDGET] = new stdClass ();
		$this->table [self::ROW_EVENT_SUMMARY] = array ();
		$this->table [self::ROW_RECLAIMED] = new stdClass ();
		$this->table [self::ROW_TRANSACTION_FEES] = new stdClass ();
		// Prime the table
		$this->table [self::ROW_AWARD_TOTALS]->annual = 0; // For the whole given year
		$this->table [self::ROW_AWARD_TOTALS]->previous_year_annual = 0; // For the whole previous year
		$this->table [self::ROW_AWARD_TOTALS]->month = 0; // For the whole month
		$this->table [self::ROW_AWARD_TOTALS]->previous_year_month = 0; // For the whole month in the previous year
		$this->table [self::ROW_BUDGET]->annual = 0; // For the whole given year
		$this->table [self::ROW_BUDGET]->previous_year_annual = 0; // For the whole previous year
		$this->table [self::ROW_BUDGET]->month = 0; // For the whole month
		$this->table [self::ROW_BUDGET]->previous_year_month = 0; // For the whole month in the previous year
		$this->table [self::ROW_RECLAIMED]->annual = 0; // For the whole given year
		$this->table [self::ROW_RECLAIMED]->previous_year_annual = 0; // For the whole previous year
		$this->table [self::ROW_RECLAIMED]->month = 0; // For the whole month
		$this->table [self::ROW_RECLAIMED]->previous_year_month = 0; // For the whole month in the previous year
		$this->table [self::ROW_TRANSACTION_FEES]->annual = 0; // For the whole given year
		$this->table [self::ROW_TRANSACTION_FEES]->previous_year_annual = 0; // For the whole previous year
		$this->table [self::ROW_TRANSACTION_FEES]->month = 0; // For the whole month
		$this->table [self::ROW_TRANSACTION_FEES]->previous_year_month = 0; // For the whole month in the previous year
		                                                                   // Annual
        $this->year = ( int ) date ( "Y" );
        $this->month = ( int ) date ( "m" );
        if ( $this->params [self::MONTH] )
            $this->month = $this->params [self::MONTH] +1;
        if ( $this->params [self::YEAR] )
           $this->year = $this->params [self::YEAR];

		$subreport_params = array ();
		$subreport_params [self::PROGRAMS] = $this->params [self::PROGRAMS];
		$subreport_params [self::DATE_BEGIN] = $this->params [self::YEAR] . '-01-01 00:00:00';
		$subreport_params [self::DATE_END] = $this->params [self::YEAR] . '-12-31 23:59:59';
		$subreport_params [self::YEAR] = $this->params [self::YEAR];
		$annual_awards_total_report = new ReportServiceAwardAudit ( $subreport_params );
		$annual_awards_total_report_table = $annual_awards_total_report->getTable ();
        if (count($annual_awards_total_report_table) > 0){
            foreach($annual_awards_total_report_table as $row) {
                $this->table [self::ROW_AWARD_TOTALS]->annual += $row->{self::FIELD_TOTAL};
            }
        }
		$annual_budget_total_report = new ReportServiceSumBudget ( $subreport_params );
		$this->table [self::ROW_BUDGET]->annual = $annual_budget_total_report->getTable ()[0]->value;

        $subreport_params [self::ACCOUNT_HOLDER_IDS] = $this->params [self::PROGRAMS];
		$subreport_params [self::ACCOUNT_TYPES] = array (
            [self::ACCOUNT_TYPE_MONIES_DUE_TO_OWNER],
			[self::ACCOUNT_TYPE_MONIES_AVAILABLE] 
		);
		$subreport_params [self::JOURNAL_EVENT_TYPES] = array (
				[self::JOURNAL_EVENT_TYPES_RECLAIM_POINTS],
                [self::JOURNAL_EVENT_TYPES_RECLAIM_MONIES] 
		);

		$annual_reclaims_report = new ReportServiceSumByAccountAndJournalEvent ( $subreport_params );
		$annual_reclaims_report_table = $annual_reclaims_report->getTable ();

        $subreport_params [self::ACCOUNT_TYPES] = array (
            [self:: ACCOUNT_TYPE_MONIES_FEES ]
            );
        $subreport_params [self::JOURNAL_EVENT_TYPES] = array (
            [self::JOURNAL_EVENT_TYPES_AWARD_POINTS_TO_RECIPIENT],
            [self::JOURNAL_EVENT_TYPES_AWARD_MONIES_TO_RECIPIENT] 
        );
        $previous_year_transaction_fees_report = new ReportServiceSumByAccountAndJournalEvent ( $subreport_params );
        $previous_year_transaction_fees_report_table = $previous_year_transaction_fees_report->getTable ();
        $subreport_params [self::SQL_GROUP_BY] = "event_name";
        $subreport_params [self::ACCOUNT_TYPES] = array (
            [self::ACCOUNT_TYPE_MONIES_FEES]
    );
    $subreport_params [self::JOURNAL_EVENT_TYPES] = array (
        [self::JOURNAL_EVENT_TYPES_AWARD_POINTS_TO_RECIPIENT],
        [self::JOURNAL_EVENT_TYPES_AWARD_MONIES_TO_RECIPIENT] 
    );
    $annual_transaction_fees_report = new ReportServiceSumByAccountAndJournalEvent ( $subreport_params );
    $annual_transaction_fees_report_table = $annual_transaction_fees_report->getTable ();
    $subreport_params [self::SQL_GROUP_BY] = "event_name";
    $annual_awards_report = new ReportServiceAwardAudit ( $subreport_params );
    $annual_awards_report_table = $annual_awards_report->getTable ();
    // $this->table[self::ROW_AWARD_TOTALS]->annual = $annual_awards_total_report_table[0]->{self::FIELD_TOTAL};
    // Previous Year Annual
    $subreport_params = array ();
    $subreport_params [self::PROGRAMS] = $this->params [self::PROGRAMS];
    $subreport_params [self::DATE_BEGIN] = ($this->params [self::YEAR] - 1) . '-01-01 00:00:00';
    $subreport_params [self::DATE_END] = ($this->params [self::YEAR] - 1) . '-12-31 23:59:59';
    $subreport_params [self::YEAR] = ($this->params [self::YEAR] - 1);

    $previous_year_awards_total_report = new ReportServiceAwardAudit ( $subreport_params );
    $previous_year_awards_total_report_table = $previous_year_awards_total_report->getTable ();
    if (count($previous_year_awards_total_report_table) > 0){
        foreach($previous_year_awards_total_report_table as $row) {

            $this->table [self::ROW_AWARD_TOTALS]->previous_year_annual += $row->{self::FIELD_TOTAL};
        }
    }
    $previous_year_budget_total_report = new ReportServiceSumBudget ( $subreport_params );
    $this->table [self::ROW_BUDGET]->previous_year_annual = $previous_year_budget_total_report->getTable ()[0]->value;
    $subreport_params [self::ACCOUNT_HOLDER_IDS] = $this->params [self::PROGRAMS];
    $subreport_params [self::ACCOUNT_TYPES] = array (
        [self::ACCOUNT_TYPE_MONIES_DUE_TO_OWNER],
        [self::ACCOUNT_TYPE_MONIES_AVAILABLE] 
    );
    $subreport_params [self::JOURNAL_EVENT_TYPES] = array (
        [self::JOURNAL_EVENT_TYPES_RECLAIM_POINTS],
        [self::JOURNAL_EVENT_TYPES_RECLAIM_MONIES] 
    );
    $previous_year_reclaims_report = new ReportServiceSumByAccountAndJournalEvent ( $subreport_params );
    $previous_year_reclaims_report_table = $previous_year_reclaims_report->getTable ();
    $subreport_params [self::ACCOUNT_TYPES] = array (
        [self::ACCOUNT_TYPE_MONIES_FEES] 
    );
    $subreport_params [self::JOURNAL_EVENT_TYPES] = array (
        [self::JOURNAL_EVENT_TYPES_AWARD_POINTS_TO_RECIPIENT],
        [self::JOURNAL_EVENT_TYPES_AWARD_MONIES_TO_RECIPIENT] 
    );
    $previous_year_transaction_fees_report = new ReportServiceSumByAccountAndJournalEvent ( $subreport_params );
    $previous_year_transaction_fees_report_table = $previous_year_transaction_fees_report->getTable ();
    $subreport_params [self::SQL_GROUP_BY] = "event_name";
    $previous_year_awards_report = new ReportServiceAwardAudit ( $subreport_params );
    $previous_year_awards_report_table = $previous_year_awards_report->getTable ();
    // Month
    $subreport_params = array ();
    $subreport_params [self::PROGRAMS] = $this->params [self::PROGRAMS];
    $subreport_params [self::DATE_BEGIN] = date ( 'Y-m-01 00:00:00', strtotime ( $this->params [self::YEAR] . '-' . $this->month ) );
    $subreport_params [self::DATE_END] = date ( 'Y-m-t 23:59:59', strtotime ( $this->params [self::YEAR] . '-' . $this->month ) );
    $subreport_params [self::MONTH] = $this->month;
    $subreport_params [self::YEAR] = $this->year;
    $month_awards_total_report = new ReportServiceAwardAudit ( $subreport_params );
    $month_awards_total_report_table = $month_awards_total_report->getTable ();
    if (count($month_awards_total_report_table) > 0) {
        foreach ($month_awards_total_report_table as $row) {
            $this->table [self::ROW_AWARD_TOTALS]->month +=$row->{self::FIELD_TOTAL};
        }
    }
    $month_budget_total_report = new ReportServiceSumBudget ( $subreport_params );
    $this->table [self::ROW_BUDGET]->month = $month_budget_total_report->getTable ()[0]->value;
    $subreport_params [self::ACCOUNT_HOLDER_IDS] = $this->params [self::PROGRAMS];
    $subreport_params [self::ACCOUNT_TYPES] = array (
        [self::ACCOUNT_TYPE_MONIES_DUE_TO_OWNER],
        [self::ACCOUNT_TYPE_MONIES_AVAILABLE] 
    );
    $subreport_params [self::JOURNAL_EVENT_TYPES] = array (
        [self::JOURNAL_EVENT_TYPES_RECLAIM_POINTS],
        [self::JOURNAL_EVENT_TYPES_RECLAIM_MONIES] 
    );
    $month_reclaims_report = new ReportServiceSumByAccountAndJournalEvent ( $subreport_params );
    $month_reclaims_report_table = $month_reclaims_report->getTable ();

    $subreport_params [self::ACCOUNT_TYPES] = array (
        [self::ACCOUNT_TYPE_MONIES_FEES] 
    );
    $subreport_params [self::JOURNAL_EVENT_TYPES] = array (
        [self::JOURNAL_EVENT_TYPES_AWARD_POINTS_TO_RECIPIENT],
        [self::JOURNAL_EVENT_TYPES_AWARD_MONIES_TO_RECIPIENT] 
    );
    $month_transaction_fees_report = new ReportServiceSumByAccountAndJournalEvent ( $subreport_params );
    $month_transaction_fees_report_table = $month_transaction_fees_report->getTable ();
    $subreport_params [self::SQL_GROUP_BY] = "event_name";
    $month_awards_report = new ReportServiceAwardAudit ( $subreport_params );
    $month_awards_report_table = $month_awards_report->getTable ();
        // Previous Year Month
    $subreport_params = array ();
    $subreport_params [self::PROGRAMS] = $this->params [self::PROGRAMS];
    $subreport_params [self::DATE_BEGIN] = date ( 'Y-m-01 00:00:00', strtotime ( ($this->params [self::YEAR] - 1) . '-' . $this->params [self::MONTH] . '-01' ) );
    $subreport_params [self::DATE_END] = date ( 'Y-m-t 23:59:59', strtotime ( ($this->params [self::YEAR] - 1) . '-' . $this->params [self::MONTH] . '-01' ) );
    $subreport_params [self::MONTH] = $this->params [self::MONTH];
    $subreport_params [self::YEAR] = ($this->params [self::YEAR] - 1);
    $previous_year_month_awards_total_report = new ReportServiceAwardAudit ( $subreport_params );
    $previous_year_month_awards_total_report_table = $previous_year_month_awards_total_report->getTable ();
    if (count($previous_year_month_awards_total_report_table) > 0 ){
        foreach($previous_year_month_awards_total_report_table as $row) {
            $this->table [self::ROW_AWARD_TOTALS]->previous_year_month += $row->{self::FIELD_TOTAL};
        }
    }
    $previous_year_month_budget_total_report = new ReportServiceSumBudget ( $subreport_params );
    $this->table [self::ROW_BUDGET]->previous_year_month = $previous_year_month_budget_total_report->getTable ()[0]->value;
    $subreport_params [self::ACCOUNT_HOLDER_IDS] = $this->params [self::PROGRAMS];
    $subreport_params [self::ACCOUNT_TYPES] = array (
        [self::ACCOUNT_TYPE_MONIES_DUE_TO_OWNER],
        [self::ACCOUNT_TYPE_MONIES_AVAILABLE ]
    );
    $subreport_params [self::JOURNAL_EVENT_TYPES] = array (
        [self::JOURNAL_EVENT_TYPES_RECLAIM_POINTS],
        [self::JOURNAL_EVENT_TYPES_RECLAIM_MONIES] 
    );
    $previous_year_month_reclaims_report = new ReportServiceSumByAccountAndJournalEvent ( $subreport_params );
    $previous_year_month_reclaims_report_table = $previous_year_month_reclaims_report->getTable ();
    $subreport_params [self::ACCOUNT_TYPES] = array (
        [self::ACCOUNT_TYPE_MONIES_FEES] 
    );
    $subreport_params [self::JOURNAL_EVENT_TYPES] = array (
        [self::JOURNAL_EVENT_TYPES_AWARD_POINTS_TO_RECIPIENT],
        [self::JOURNAL_EVENT_TYPES_AWARD_MONIES_TO_RECIPIENT] 
    );
    $previous_year_month_transaction_fees_report = new ReportServiceSumByAccountAndJournalEvent ( $subreport_params );
    $previous_year_month_transaction_fees_report_table = $previous_year_month_transaction_fees_report->getTable ();
    $subreport_params [self::SQL_GROUP_BY] = "event_name";
    $previous_year_month_awards_report = new ReportServiceAwardAudit ( $subreport_params );
    $previous_year_month_awards_report_table = $previous_year_month_awards_report->getTable ();
    // Finalize the report by sorting all of the events by name
    $filter = array();
    $filter['year'] = $this->year;
    $filter['month'] = $this->month;
    $this->table["filter"] = $filter;
    $all_events = array_merge ( $annual_awards_report_table, $previous_year_awards_report_table, $month_awards_report_table, $previous_year_month_awards_report_table );
    if (is_array ( $all_events ) && count ( $all_events ) > 0) {
        foreach ( $all_events as $event ) {
            $event_place_holder = new stdClass ();
            $event_place_holder->event_name = $event->event_name;
            $event_place_holder->annual = 0;
            $event_place_holder->previous_year_annual = 0;
            $event_place_holder->month = 0;
            $event_place_holder->previous_year_month = 0;
            $this->table [self::ROW_EVENT_SUMMARY] [$event->event_name] = $event_place_holder;
        }
    }
    	if (is_array ( $annual_awards_report_table ) && count ( $annual_awards_report_table ) > 0) {
			foreach ( $annual_awards_report_table as $event ) {
				$this->table [self::ROW_EVENT_SUMMARY] [$event->event_name]->annual += $event->{self::FIELD_TOTAL};
			}
		}
		// Sort the reclaims
		if (is_array ( $annual_reclaims_report_table ) && count ( $annual_reclaims_report_table ) > 0) {
			foreach ( $annual_reclaims_report_table as $program_account_holder_id => $programs_annual_reclaims_report_table ) {
				// Reclaimed Points
				if (isset ( $programs_annual_reclaims_report_table ->ACCOUNT_TYPE_MONIES_DUE_TO_OWNER )) {
					if (isset ( $programs_annual_reclaims_report_table ->ACCOUNT_TYPE_MONIES_DUE_TO_OWNER ->JOURNAL_EVENT_TYPES_RECLAIM_POINTS )) {
						$this->table [self::ROW_RECLAIMED]->annual += $programs_annual_reclaims_report_table ->ACCOUNT_TYPE_MONIES_DUE_TO_OWNER ->JOURNAL_EVENT_TYPES_RECLAIM_POINTS;
					}
				}
				// Reclaimed Monies
				if (isset ( $programs_annual_reclaims_report_table ->ACCOUNT_TYPE_MONIES_AVAILABLE )) {
					if (isset ( $programs_annual_reclaims_report_table ->ACCOUNT_TYPE_MONIES_AVAILABLE ->JOURNAL_EVENT_TYPES_RECLAIM_MONIES )) {
						$this->table [self::ROW_RECLAIMED]->annual += $programs_annual_reclaims_report_table ->ACCOUNT_TYPE_MONIES_AVAILABLE ->JOURNAL_EVENT_TYPES_RECLAIM_MONIES;
					}
				}
			}
		}
		// Sort the transaction fees
		if (is_array ( $annual_transaction_fees_report_table ) && count ( $annual_transaction_fees_report_table ) > 0) {
			foreach ( $annual_transaction_fees_report_table as $program_account_holder_id => $programs_annual_transaction_fees_report_table ) {
				if (isset ( $programs_annual_transaction_fees_report_table ->ACCOUNT_TYPE_MONIES_FEES )) {
					if (isset ( $programs_annual_transaction_fees_report_table ->ACCOUNT_TYPE_MONIES_FEES ->JOURNAL_EVENT_TYPES_AWARD_POINTS_TO_RECIPIENT )) {
						$this->table [self::ROW_TRANSACTION_FEES]->annual += $programs_annual_transaction_fees_report_table ->ACCOUNT_TYPE_MONIES_FEES ->JOURNAL_EVENT_TYPES_AWARD_POINTS_TO_RECIPIENT;
					}
					if (isset ( $programs_annual_transaction_fees_report_table ->ACCOUNT_TYPE_MONIES_FEES ->JOURNAL_EVENT_TYPES_AWARD_MONIES_TO_RECIPIENT )) {
						$this->table [self::ROW_TRANSACTION_FEES]->annual += $programs_annual_transaction_fees_report_table ->ACCOUNT_TYPE_MONIES_FEES ->JOURNAL_EVENT_TYPES_AWARD_MONIES_TO_RECIPIENT;
					}
				}
			}
		}
		// Previous Year Annual
		if (is_array ( $previous_year_awards_report_table ) && count ( $previous_year_awards_report_table ) > 0) {
			foreach ( $previous_year_awards_report_table as $event ) {
				$this->table [self::ROW_EVENT_SUMMARY] [$event->event_name]->previous_year_annual += $event->{self::FIELD_TOTAL};
			}
		}
		// Sort the reclaims
		if (is_array ( $previous_year_reclaims_report_table ) && count ( $previous_year_reclaims_report_table ) > 0) {
			foreach ( $previous_year_reclaims_report_table as $program_account_holder_id => $programs_previous_year_reclaims_report_table ) {
				// Reclaimed Points
				if (isset ( $programs_previous_year_reclaims_report_table ->ACCOUNT_TYPE_MONIES_DUE_TO_OWNER )) {
					if (isset ( $programs_previous_year_reclaims_report_table ->ACCOUNT_TYPE_MONIES_DUE_TO_OWNER ->JOURNAL_EVENT_TYPES_RECLAIM_POINTS )) {
						$this->table [self::ROW_RECLAIMED]->previous_year_annual += $programs_previous_year_reclaims_report_table ->ACCOUNT_TYPE_MONIES_DUE_TO_OWNER ->JOURNAL_EVENT_TYPES_RECLAIM_POINTS;
					}
				}
				// Reclaimed Monies
				if (isset ( $programs_previous_year_reclaims_report_table ->ACCOUNT_TYPE_MONIES_AVAILABLE )) {
					if (isset ( $programs_previous_year_reclaims_report_table ->ACCOUNT_TYPE_MONIES_AVAILABLE ->JOURNAL_EVENT_TYPES_RECLAIM_MONIES )) {
						$this->table [self::ROW_RECLAIMED]->previous_year_annual += $programs_previous_year_reclaims_report_table ->ACCOUNT_TYPE_MONIES_AVAILABLE ->JOURNAL_EVENT_TYPES_RECLAIM_MONIES;
					}
				}
			}
		}
		// Sort the transaction fees
		if (is_array ( $previous_year_transaction_fees_report_table ) && count ( $previous_year_transaction_fees_report_table ) > 0) {
			foreach ( $previous_year_transaction_fees_report_table as $program_account_holder_id => $programs_previous_year_transaction_fees_report_table ) {
				if (isset ( $programs_previous_year_transaction_fees_report_table ->ACCOUNT_TYPE_MONIES_FEES )) {
					if (isset ( $programs_previous_year_transaction_fees_report_table ->ACCOUNT_TYPE_MONIES_FEES ->JOURNAL_EVENT_TYPES_AWARD_POINTS_TO_RECIPIENT )) {
						$this->table [self::ROW_TRANSACTION_FEES]->previous_year_annual += $programs_previous_year_transaction_fees_report_table ->ACCOUNT_TYPE_MONIES_FEES ->JOURNAL_EVENT_TYPES_AWARD_POINTS_TO_RECIPIENT;
					}
					if (isset ( $programs_previous_year_transaction_fees_report_table ->ACCOUNT_TYPE_MONIES_FEES ->JOURNAL_EVENT_TYPES_AWARD_MONIES_TO_RECIPIENT )) {
						$this->table [self::ROW_TRANSACTION_FEES]->previous_year_annual += $programs_previous_year_transaction_fees_report_table ->ACCOUNT_TYPE_MONIES_FEES ->JOURNAL_EVENT_TYPES_AWARD_MONIES_TO_RECIPIENT;
					}
				}
			}
		}
		// Month
		if (is_array ( $month_awards_report_table ) && count ( $month_awards_report_table ) > 0) {
			foreach ( $month_awards_report_table as $event ) {
				$this->table [self::ROW_EVENT_SUMMARY] [$event->event_name]->month += $event->{self::FIELD_TOTAL};
			}
		}
		// Sort the reclaims
		if (is_array ( $month_reclaims_report_table ) && count ( $month_reclaims_report_table ) > 0) {
			foreach ( $month_reclaims_report_table as $program_account_holder_id => $programs_month_reclaims_report_table ) {
				// Reclaimed Points
				if (isset ( $programs_month_reclaims_report_table ->ACCOUNT_TYPE_MONIES_DUE_TO_OWNER )) {
					if (isset ( $programs_month_reclaims_report_table ->ACCOUNT_TYPE_MONIES_DUE_TO_OWNER ->JOURNAL_EVENT_TYPES_RECLAIM_POINTS )) {
						$this->table [self::ROW_RECLAIMED]->month += $programs_month_reclaims_report_table ->ACCOUNT_TYPE_MONIES_DUE_TO_OWNER ->JOURNAL_EVENT_TYPES_RECLAIM_POINTS;
					}
				}
				// Reclaimed Monies
				if (isset ( $programs_month_reclaims_report_table ->ACCOUNT_TYPE_MONIES_AVAILABLE )) {
					if (isset ( $programs_month_reclaims_report_table ->ACCOUNT_TYPE_MONIES_AVAILABLE ->JOURNAL_EVENT_TYPES_RECLAIM_MONIES )) {
						$this->table [self::ROW_RECLAIMED]->month += $programs_month_reclaims_report_table ->ACCOUNT_TYPE_MONIES_AVAILABLE ->JOURNAL_EVENT_TYPES_RECLAIM_MONIES;
					}
				}
			}
		}
		// Sort the transaction fees
		if (is_array ( $month_transaction_fees_report_table ) && count ( $month_transaction_fees_report_table ) > 0) {
			foreach ( $month_transaction_fees_report_table as $program_account_holder_id => $programs_month_transaction_fees_report_table ) {
				if (isset ( $programs_month_transaction_fees_report_table ->ACCOUNT_TYPE_MONIES_FEES )) {
					if (isset ( $programs_month_transaction_fees_report_table ->ACCOUNT_TYPE_MONIES_FEES ->JOURNAL_EVENT_TYPES_AWARD_POINTS_TO_RECIPIENT )) {
						$this->table [self::ROW_TRANSACTION_FEES]->month += $programs_month_transaction_fees_report_table ->ACCOUNT_TYPE_MONIES_FEES ->JOURNAL_EVENT_TYPES_AWARD_POINTS_TO_RECIPIENT;
					}
					if (isset ( $programs_month_transaction_fees_report_table ->ACCOUNT_TYPE_MONIES_FEES ->JOURNAL_EVENT_TYPES_AWARD_MONIES_TO_RECIPIENT )) {
						$this->table [self::ROW_TRANSACTION_FEES]->month += $programs_month_transaction_fees_report_table ->ACCOUNT_TYPE_MONIES_FEES ->JOURNAL_EVENT_TYPES_AWARD_MONIES_TO_RECIPIENT;
					}
				}
			}
		}
		// Previous Year Month
		if (is_array ( $previous_year_month_awards_report_table ) && count ( $previous_year_month_awards_report_table ) > 0) {
			foreach ( $previous_year_month_awards_report_table as $event ) {
				$this->table [self::ROW_EVENT_SUMMARY] [$event->event_name]->previous_year_month += $event->{self::FIELD_TOTAL};
			}
		}
		// Sort the reclaims
		if (is_array ( $previous_year_month_reclaims_report_table ) && count ( $previous_year_month_reclaims_report_table ) > 0) {
			foreach ( $previous_year_month_reclaims_report_table as $program_account_holder_id => $programs_previous_year_month_reclaims_report_table ) {
				// Reclaimed Points
				if (isset ( $programs_previous_year_month_reclaims_report_table ->ACCOUNT_TYPE_MONIES_DUE_TO_OWNER )) {
					if (isset ( $programs_previous_year_month_reclaims_report_table ->ACCOUNT_TYPE_MONIES_DUE_TO_OWNER ->JOURNAL_EVENT_TYPES_RECLAIM_POINTS )) {
						$this->table [self::ROW_RECLAIMED]->previous_year_month += $programs_previous_year_month_reclaims_report_table ->ACCOUNT_TYPE_MONIES_DUE_TO_OWNER ->JOURNAL_EVENT_TYPES_RECLAIM_POINTS;
					}
				}
				// Reclaimed Monies
				if (isset ( $programs_previous_year_month_reclaims_report_table ->ACCOUNT_TYPE_MONIES_AVAILABLE )) {
					if (isset ( $programs_previous_year_month_reclaims_report_table ->ACCOUNT_TYPE_MONIES_AVAILABLE ->JOURNAL_EVENT_TYPES_RECLAIM_MONIES )) {
						$this->table [self::ROW_RECLAIMED]->previous_year_month += $programs_previous_year_month_reclaims_report_table ->ACCOUNT_TYPE_MONIES_AVAILABLE ->JOURNAL_EVENT_TYPES_RECLAIM_MONIES;
					}
				}
			}
		}
		// Sort the transaction fees
		if (is_array ( $previous_year_month_transaction_fees_report_table ) && count ( $previous_year_month_transaction_fees_report_table ) > 0) {
			foreach ( $previous_year_month_transaction_fees_report_table as $program_account_holder_id => $programs_previous_year_month_transaction_fees_report_table ) {
				if (isset ( $programs_previous_year_month_transaction_fees_report_table ->ACCOUNT_TYPE_MONIES_FEES )) {
					if (isset ( $programs_previous_year_month_transaction_fees_report_table ->ACCOUNT_TYPE_MONIES_FEES ->JOURNAL_EVENT_TYPES_AWARD_POINTS_TO_RECIPIENT )) {
						$this->table [self::ROW_TRANSACTION_FEES]->month += $programs_previous_year_month_transaction_fees_report_table ->ACCOUNT_TYPE_MONIES_FEES ->JOURNAL_EVENT_TYPES_AWARD_POINTS_TO_RECIPIENT;
					}
					if (isset ( $programs_previous_year_month_transaction_fees_report_table ->ACCOUNT_TYPE_MONIES_FEES ->JOURNAL_EVENT_TYPES_AWARD_MONIES_TO_RECIPIENT )) {
						$this->table [self::ROW_TRANSACTION_FEES]->month += $programs_previous_year_month_transaction_fees_report_table ->ACCOUNT_TYPE_MONIES_FEES ->JOURNAL_EVENT_TYPES_AWARD_MONIES_TO_RECIPIENT;
					}
				}
			}
		}
		// reset the keys on the event summary array
		$this->table [self::ROW_EVENT_SUMMARY] = array_values ( $this->table [self::ROW_EVENT_SUMMARY] );
    return $this->table;

    }

    public function getCsvHeaders(): array
    {
        if ($this->params[self::SERVER] === 'program'){
            return [
                [
                    'label' => 'Event',
                    'key' => 'event_name'
                ],
                [
                    'label' => 'GL Code',
                    'key' => 'ledger_code'
                ],
                [
                    'label' => 'Date',
                    'key' => 'posting_timestamp'
                ],
                [
                    'label' => 'First Name',
                    'key' => 'recipient_first_name'
                ],
                [
                    'label' => 'Last Name',
                    'key' => 'recipient_last_name'
                ],
                [
                    'label' => 'Email',
                    'key' => 'recipient_email'
                ],
                [
                    'label' => 'From',
                    'key' => 'awarder_full'
                ],
                [
                    'label' => 'Referrer',
                    'key' => 'referrer'
                ],
                [
                    'label' => 'Notes',
                    'key' => 'notes'
                ],
                [
                    'label' => 'Dollar Value',
                    'key' => 'dollar_value'
                ],
            ];
        } else {
            return [
                [
                    'label' => 'Program Name',
                    'key' => 'program_name'
                ],
                [
                    'label' => 'Program Id',
                    'key' => 'program_id'
                ],
                [
                    'label' => 'External Id',
                    'key' => 'external_id'
                ],
                [
                    'label' => 'Event',
                    'key' => 'event_name'
                ],
                [
                    'label' => 'GL Code',
                    'key' => 'ledger_code'
                ],
                [
                    'label' => 'Award Level',
                    'key' => 'award_level_name'
                ],
                [
                    'label' => 'Date',
                    'key' => 'posting_timestamp'
                ],
                [
                    'label' => 'First Name',
                    'key' => 'recipient_first_name'
                ],
                [
                    'label' => 'Program Name',
                    'key' => 'program_name'
                ],
                [
                    'label' => 'Last Name',
                    'key' => 'recipient_last_name'
                ],
                [
                    'label' => 'Email',
                    'key' => 'recipient_email'
                ],
                [
                    'label' => 'From',
                    'key' => 'awarder_full'
                ],
                [
                    'label' => 'Referrer',
                    'key' => 'referrer'
                ],
                [
                    'label' => 'Notes',
                    'key' => 'notes'
                ],
                [
                    'label' => 'Value',
                    'key' => 'points'
                ],
                [
                    'label' => 'Dollar Value',
                    'key' => 'dollar_value'
                ],
            ];
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
