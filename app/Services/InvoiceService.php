<?php
namespace App\Services;

use App\Services\Program\ChargeInvoiceForMoniesPending;
use App\Services\Program\ChargeInvoiceForDespositFee;
use App\Services\Program\ReadCompiledInvoiceService;
use App\Services\Program\ReadInvoicePaymentsService;
use App\Services\Program\CreateInvoiceService;
use App\Services\InvoicePaymentService;
use App\Services\reports\ReportFactory;
use App\Models\Traits\InvoiceFilters;
use App\Models\Traits\Filterable;
use App\Models\JournalEventType;
use App\Models\PaymentMethod;
use App\Models\Program;
use App\Models\Invoice;

class InvoiceService
{
    use Filterable, InvoiceFilters;

    private ProgramService $programService;
    private CreateInvoiceService $createInvoiceService;
    private InvoicePaymentService $invoicePaymentService;
    private ReadInvoicePaymentsService $readInvoicePaymentsService;
    private ReportFactory $reportFactory;

    public function __construct(
        ProgramService $programService,
        CreateInvoiceService $createInvoiceService,
		InvoicePaymentService $invoicePaymentService,
        ReadInvoicePaymentsService $readInvoicePaymentsService,
        ReportFactory $reportFactory
    ) {
        $this->programService = $programService;
        $this->createInvoiceService = $createInvoiceService;
        $this->invoicePaymentService = $invoicePaymentService;
        $this->readInvoicePaymentsService = $readInvoicePaymentsService;
        $this->reportFactory = $reportFactory;
    }

    public function index( $program, $paginate = true ) {
        $program = self::GetModelByMixed($program);
        if( !$program->exists() ) return;
        // $query = Invoice::query();
        // self::$query = Invoice::whereHas('roles', function (Builder $query) use($program) {
        //     $query->where('name', 'LIKE', config('roles.participant'))
        //     ->where('model_has_roles.program_id', $program->id);
        // });
        // pr(DB::enableQueryLog());
        $query = self::filterable(Invoice::class);
        $query = $query->where('program_id', $program->id);
        if( $paginate ) {
            $invoices = $query->paginate( self::$PARAMS['limit'] );
        }   else    {
            $invoices = $query->get();
        }
        return $invoices;
    }

	public function createOnDemand($data, $program) {
		// payment method
        $amount = $data['amount'];
		$deposit_fee = $program->deposit_fee / 100.0;
		$deposit_fee_amount = $deposit_fee * $amount;

		$payment_method_id = PaymentMethod::getPaymentMethodCheck(true);

		// create a new invoice
		$date_begin = $date_end = date ( 'Y-m-d' );
		$days_to_pay = isset($data['days_to_pay']) && $data['days_to_pay'] > 0 ? $data['days_to_pay'] : Invoice::DAYS_TO_PAY;

		$invoice = $this->createInvoiceService->process($program, "On-Demand", $date_begin, $date_end, $days_to_pay, $payment_method_id);

		$user = auth()->user();

		if( $invoice )  {
            $invoice = (new ChargeInvoiceForMoniesPending())->process($invoice, $user, $program, $amount );
            if ($deposit_fee > 0) {
            	$invoice = (new ChargeInvoiceForDespositFee())->process ($invoice, $user, $program, $deposit_fee_amount);
            }
            // sleep(2); //To Remove
        }
	}

    public function getInvoice(Invoice $invoice)   {
        if( !$invoice->exists() ) return null;
        $invoice->load(['program', 'program.address', 'invoice_type', 'journal_events']);
        $readCompiledInvoiceService = resolve(ReadCompiledInvoiceService::class);
        $invoice = $readCompiledInvoiceService->get($invoice);
        return $invoice;
    }

	public function getPayableInvoice(Invoice $invoice)   {
		if( !$invoice->exists() ) return null;
		$view_params = [];

        $invoice->load(['program', 'program.address', 'invoice_type', 'journal_events']);
        $readCompiledInvoiceService = resolve(ReadCompiledInvoiceService::class);
        $invoice = $readCompiledInvoiceService->get($invoice);

		$payments = $this->readInvoicePaymentsService->get($invoice);

		// Create a simpler data structure for the view
		$invoice_for_view = new \stdClass ();
		$invoice_for_view->invoice_id = $invoice->id;
		$invoice_for_view->invoice_number = $invoice->invoice_number;
		$invoice_for_view->parent_program_name = $invoice->program->name;
		$invoice_for_view->date_end = $invoice->date_end;
		$invoice_for_view->statements = array ();
		$invoice_program_ids = array ();

		foreach ( $invoice->invoices as $statement ) {
		    //$invoice_program_ids [] = ( int ) $statement ['info']->program_id; - current code
			$invoice_program_ids [] = ( int ) $statement ['info']->program_account_holder_id;
		}
		// $report_data = Report::read_journal_entry_detail ( $invoice_program_ids, $invoice->date_begin, $invoice->date_end, 0, 99999 );
        $report = $this->reportFactory->build("JournalDetailed", ['programs' => $invoice_program_ids, 'from' => $invoice->date_begin, 'to' => $invoice->date_end]);
        $report_data = $report->getReport();

		foreach ( $invoice->invoices as $statement ) {
			// Create a line item for the program

			$program_statement = new \stdClass ();
			$program_statement->program_id = $statement ['info']->program_id;
			$program_statement->program_name = $statement ['info']->program_name;
			$program_statement->program_account_holder_id = $statement ['info']->program_account_holder_id;
			$program_statement->charges = array ();

			//return $report_data;

			$data = $report_data[$statement['info']->program_account_holder_id];
			// dd($data->invoice_for_awards);
			// pr($data->toArray());
			// exit;
			if ($data->invoice_for_awards) {
				// Mapping of the items to pull out of the journal report and the corresponding payment journal event type that needs to be used to pay for it
				$report_items_to_charge = array (
						'points_purchased' => JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_POINTS,
						'transaction_fees' => JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_POINTS_TRANSACTION_FEE, // Special case to handle transaction fees, as a business rule we dont have these on "Invoice for Awards" Programs
						'admin_fee' => JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_ADMIN_FEE,
						'usage_fee' => JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_MONTHLY_USAGE_FEE,
						'setup_fee' => JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_SETUP_FEE,
						'fixed_fee' => JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_FIXED_FEE
				);
				// pr($report_items_to_charge);
				// Mapping of the refund items to pull out of the journal report and the corresponding charge that should be credited for it
				$report_items_to_refund = array (
						'reclaims' => JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_POINTS,
						'refunded_transaction_fees' => JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_POINTS_TRANSACTION_FEE
				);
				// pr($report_items_to_refund);
				// exit;
			} else {
				// Mapping of the items to pull out of the journal report and the corresponding payment journal event type that needs to be used to pay for it
				$report_items_to_charge = array (
						// 'points_purchased' => JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_POINTS,
						// 'transaction_fees' => "transaction fee", //Special case to handle transaction fees, as a business rule we dont have these on "Invoice for Awards" Programs
						'admin_fee' => JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_ADMIN_FEE,
						'usage_fee' => JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_MONTHLY_USAGE_FEE,
						'setup_fee' => JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_SETUP_FEE,
						'fixed_fee' => JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_FIXED_FEE
				);
				// Mapping of the refund items to pull out of the journal report and the corresponding charge that should be credited for it
				$report_items_to_refund = array ();
				// 'reclaims' => JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_POINTS,
				// 'refunded_transaction_fees' => "transaction fee"
				//Charges for Air program - pay in advance
				$charges_for_pay_in_advance = array(
					JournalEventType::JOURNAL_EVENT_TYPES_CHARGE_MONIES_PENDING => JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_MONIES_PENDING,
					JournalEventType::JOURNAL_EVENT_TYPES_CHARGE_DEPOSIT_FEE => JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_DEPOSIT_FEE,
				);
			}
			// Group the charges
			foreach ( $report_items_to_charge as $report_index => $journal_event_type ) {
				// pr([$journal_event_type, $report_index, $data->$report_index]);
				// continue;
				if ($data->$report_index <= 0) {
					continue;
				}
				// Check to see if we already have a debit of this journal event type, if not create it
				if (! isset ( $program_statement->charges [$journal_event_type] )) {
					$debit_line_item = new \stdClass ();
					$debit_line_item->total = 0;
					$debit_line_item->due = 0;
					$debit_line_item->refunds = 0;
					$debit_line_item->payments = 0;
					$program_statement->charges [$journal_event_type] = $debit_line_item;
				}
				// Add the charge to the line item
				$program_statement->charges [$journal_event_type]->total += $data->$report_index;
				$program_statement->charges [$journal_event_type]->due += $data->$report_index;
			}
			// Group the refunds
			foreach ( $report_items_to_refund as $report_index => $journal_event_type ) {
				// Check to see if we already have a debit of this journal event type, if not create it
				if (! isset ( $program_statement->charges [$journal_event_type] )) {
					$debit_line_item = new \stdClass ();
					$debit_line_item->total = 0;
					$debit_line_item->due = 0;
					$debit_line_item->refunds = 0;
					$debit_line_item->payments = 0;
					$program_statement->charges [$journal_event_type] = $debit_line_item;
				}
				// Add the charge to the line item
				// $program_statement->charges[$journal_event_type]->total -= $data->$report_index;
				$program_statement->charges [$journal_event_type]->refunds += - 1 * $data->$report_index;
				$program_statement->charges [$journal_event_type]->due -= $data->$report_index;
			}
			//Charges for Pay in invoice
			if (isset($charges_for_pay_in_advance) && !empty($charges_for_pay_in_advance)) {
				foreach ($invoice->invoices as $invoice_data) {
					if (is_array($invoice_data['info']->debits) && count($invoice_data['info']->debits) > 0) {
						foreach($invoice_data['info']->debits as $row) {
							if ($row->amount == 0 ) {
								continue;
							}
							$journal_event_type = isset($charges_for_pay_in_advance[$row->journal_event_type])
								? $charges_for_pay_in_advance[$row->journal_event_type] : $row->journal_event_type;
							if (in_array($row->journal_event_type, array_keys($charges_for_pay_in_advance))) {
								if (! isset ( $program_statement->charges [$journal_event_type] )) {
									$debit_line_item = new \stdClass ();
									$debit_line_item->total = 0;
									$debit_line_item->due = 0;
									$debit_line_item->refunds = 0;
									$debit_line_item->payments = 0;
									$program_statement->charges [$journal_event_type] = $debit_line_item;
								}
								// Add the charge to the line item
								$program_statement->charges [$journal_event_type]->total -= $row->amount;
								$program_statement->charges [$journal_event_type]->due -= $row->amount;
							}
						}
					}
				}
			}
			$invoice_for_view->statements[$program_statement->program_account_holder_id] = $program_statement;
		}
		// Subtract payments from each type
		if (is_array ( $payments ) && count ( $payments ) > 0) {
			foreach ( $payments as $payment ) {
				// Check to see if we already have a debit of this journal event type, if not create it
				if (! isset ( $invoice_for_view->statements [$payment->program_account_holder_id]->charges [$payment->journal_event_type] )) {
					$debit_line_item = new \stdClass ();
					$debit_line_item->total = 0;
					$debit_line_item->due = 0;
					$debit_line_item->refunds = 0;
					$debit_line_item->payments = 0;
					$invoice_for_view->statements [$payment->program_account_holder_id]->charges [$payment->journal_event_type] = $debit_line_item;
				}
				// Add the charge to the line item
				// $program_statement->charges[$payment->journal_event_type]->total -= $payment->amount;
				$invoice_for_view->statements [$payment->program_account_holder_id]->charges [$payment->journal_event_type]->due -= $payment->amount;
				$invoice_for_view->statements [$payment->program_account_holder_id]->charges [$payment->journal_event_type]->payments += - 1 * $payment->amount;
			}
		}
		if (isset ( $invoice ['invoice'] [0] ['info']->journal_summary )) {
			$view_params ['journal_summary'] = $invoice ['invoice'] [0] ['info']->journal_summary;
		}
		$view_params ['total_start_balance'] = $invoice->total_start_balance;
		$view_params ['total_end_balance'] = $invoice->total_end_balance;
		$view_params ['total_invoice_amount'] = $invoice->total_invoice_amount;
		// $view_params ['total_payments'] = $invoice ['total_payments'];
		// $view_params ['invoice_data'] = $invoice ['invoice_data'];
		// $view_params['invoice'] = $invoice_data['invoice'];
		sort($invoice_for_view->statements);
		$view_params ['invoice'] = $invoice_for_view;
		$view_params ['payments'] = $payments;
		$invoice->view_params = $view_params;
		return $invoice;
	}

	public function submitPayment(Invoice $invoice, $validated)	{
		$response = [];
		$this->invoicePaymentService->setInvoice($invoice);
		// return $validated;
		$notes = $validated['notes'];
		foreach( $validated['applied_payments'] as $appliedPayment)	{
			$program_id = $appliedPayment['program_id'];
			if(isset($appliedPayment['payments']) && $appliedPayment['payments'])	{
				// pr($appliedPayment['payments']);
				foreach($appliedPayment['payments'] as $jet => $amount)	{
					if ($amount <= 0) {
						continue;
					}
					// pr($jet);
					try {
						switch ($jet) {
							case JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_ADMIN_FEE :
								$res = $this->invoicePaymentService->program_pays_for_admin_fee ( $program_id, $amount, $notes);
								$response['success'][] = "Program paid for admin fee successfully...";
								break;
							case JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_SETUP_FEE :
								$res = $this->invoicePaymentService->program_pays_for_setup_fee ( $program_id, $amount, $notes );
								$response['success'][] = "Program paid for setup fee successfully...";
								break;
							case JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_MONTHLY_USAGE_FEE :
								$res = $this->invoicePaymentService->program_pays_for_usage_fee ( $program_id, $amount, $notes );
								$response['success'][] = "Program paid for usage fee successfully...";
								break;
							case JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_FIXED_FEE :
								$res = $this->invoicePaymentService->program_pays_for_fixed_fee ( $this->program_id, $amount, $notes );
								$response['success'][] = "Program paid for fixed fee successfully...";
								break;
								break;
							case JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_POINTS :
								$res = $this->invoicePaymentService->program_pays_for_points ( $program_id, $amount, $notes );
								$response['success'][] = "Program paid for points successfully...";
								break;
							case JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_POINTS_TRANSACTION_FEE :
								$this->invoicePaymentService->program_pays_for_points_transaction_fee ( $program_id, $amount, $notes );
								$response['success'][] = "Program paid for transaction fee successfully...";
								break;
							case JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_DEPOSIT_FEE :
								$this->invoicePaymentService->program_pays_for_deposit_fee ( $program_id, $amount, $notes);
								$response['success'][] = "Program paid for deposit fee successfully...";
								break;
							case JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_MONIES_PENDING :
								$this->invoicePaymentService->program_pays_for_monies_pending ( $program_id, $amount, $notes );
								$response['success'][] = "Program paid for monies pending successfully...";
								break;
						}
					} catch ( \Exception $e ) {
						return ['errors' => sprintf('Exception with error: %s on line: %d in InvoiceService', $e->getMessage(), $e->getLine())];
					}
				}
			}
		}
		return $response;
	}
}
