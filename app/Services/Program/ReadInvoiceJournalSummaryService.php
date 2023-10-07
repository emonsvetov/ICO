<?php
namespace App\Services\Program;

use App\Services\Program\ReadInvoicePaymentsService;
use App\Services\StatementService;
use App\Services\ProgramService;
use App\Models\InvoiceType;
use App\Models\Invoice;
use App\Models\Program;
use App\Models\Report;
use DB;

class ReadInvoiceJournalSummaryService
{
    protected $reportFactory;
    protected StatementService $statementService;
    protected ReadInvoicePaymentsService $readInvoicePaymentsService;

	public function __construct(
        ProgramService $programService,
        StatementService $statementService,
        ReadInvoicePaymentsService $readInvoicePaymentsService,
    ) {
        $this->statementService = $statementService;
        $this->readInvoicePaymentsService = $readInvoicePaymentsService;
    }

    public function get(Program $program, $start_date, $end_date) {

		$program_account_holder_ids = [$program->account_holder_id]; // push first entry
		/*
		 * outline:
		 *
		 * 1) get list of IDs for programs under this program that are !bill_direct
		 * 2) run report query for journal details
		 */

		$billable_sub_programs = resolve(\App\Services\ProgramService::class)->getBillableDescendants ( $program );

		if (is_array ( $billable_sub_programs ) && count ( $billable_sub_programs ) > 0) {
			foreach ( $billable_sub_programs as $sub_program ) {
				$program_account_holder_ids[] = ( int ) $sub_program->id;
			}
		}

        $this->reportFactory = new \App\Services\reports\ReportFactory();
        $report = $this->reportFactory->build("JournalDetailed", ['programs' => $program_account_holder_ids, 'from' => $start_date, 'to' => $end_date]);
        // $report_data = $report->getReport();

		$report = $report->getReport();

		$summary = $report[$program->account_holder_id];

		if (! isset ( $summary )) {
			// no data was returned...
			// TODO: do something
			throw new \RuntimeException ( "Invoice Journal Summary Returned No Data." );
		}

		$result = array ();
		// $result[1]['total_awards']= 0.0;
		// $result[2]['total_txn_fees']= 0.0;
		// $result[3]['total_admin_fees']= 0.0;
		// $result[4]['total_setup_fees']= 0.0;
		// $result[5]['total_usage_fees']= 0.0;
		$result ['grand_total'] = 0.0;
		$TOTAL_AWARDS = 'Total Awards';
		$TOTAL_TXN_FEES = 'Total Transaction Fees';
		$TOTAL_ADMIN_FEES = 'Total Admin Fees';
		$TOTAL_SETUP_FEES = 'Total Setup Fees';
		$TOTAL_USAGE_FEES = 'Total Usage Fees';
		$TOTAL_FIXED_FEES = 'Total Monthly Account Fees';
		$TOTAL_REFUNDS = 'Total Refunds';
		if($program->air_premium_cost_to_program){
			$TOTAL_PREMIUM = 'Total Premium cost to program';
		}
		$result [1] [$TOTAL_AWARDS] = 0;
		$result [2] [$TOTAL_TXN_FEES] = 0;
		$result [3] [$TOTAL_ADMIN_FEES] = 0;
		$result [4] [$TOTAL_SETUP_FEES] = 0;
		$result [5] [$TOTAL_USAGE_FEES] = 0;
		$result [6] [$TOTAL_FIXED_FEES] = 0;
		$result [7] [$TOTAL_REFUNDS] = 0;
		if($program->air_premium_cost_to_program){
			$result [8] [$TOTAL_PREMIUM] = 0;
		}

		$i = 0;
		foreach ( $report as $data ) {
			if ($data->invoice_for_awards) {
				$result [1] [$TOTAL_AWARDS] += ( float ) $data->points_purchased;
				$result [2] [$TOTAL_TXN_FEES] += ( float ) $data->transaction_fee;
				$result [7] [$TOTAL_REFUNDS] -= ( float ) $data->reclaims + ( float ) $data->refunded_transaction_fee;
			}
			$result [3] [$TOTAL_ADMIN_FEES] += ( float ) $data->admin_fee;
			$result [4] [$TOTAL_SETUP_FEES] += ( float ) $data->setup_fee;
			$result [5] [$TOTAL_USAGE_FEES] += ( float ) $data->usage_fee;
			$result [6] [$TOTAL_FIXED_FEES] += ( float ) $data->fixed_fee;
			// if($program->air_premium_cost_to_program){ //TODO - not sure if need to make it work
			// 	$premiumFees = $this->getPremiumFee($program_account_holder_ids, $start_date, $end_date);
			// 	if($result [8] [$TOTAL_PREMIUM] == 0){
			// 		foreach($premiumFees as $premiumFee){
			// 			$result [8] [$TOTAL_PREMIUM] += $premiumFee->premiumamount;
			// 		}
			// 	}
			// }

			// dd($result);

			// NOTICE the SUBTRACTION:
			// $result[1]['total_reclaimed'] -= (float)$data->reclaims;
		}
		foreach ( $result as $i => $line ) {
			if ($i == 'grand_total') {
				continue;
			}
			foreach ( $line as $description => $amount ) {
				$result ['grand_total'] += $amount;
			}
		}
		return $result;
	}
}
