<?php
namespace App\Services\Program;

use App\Services\Program\ReadInvoiceJournalSummaryService;
use App\Services\Program\ReadInvoicePaymentsService;
use App\Services\StatementService;
use App\Services\ProgramService;
use App\Models\InvoiceType;
use App\Models\Invoice;
use DB;

class ReadCompiledInvoiceService
{
	public function __construct(
		ProgramService $programService,
        StatementService $statementService,
        ReadInvoicePaymentsService $readInvoicePaymentsService,
        ReadInvoiceJournalSummaryService $readInvoiceJournalSummaryService,
    ) {
		$this->programService = $programService;
        $this->statementService = $statementService;
        $this->readInvoicePaymentsService = $readInvoicePaymentsService;
        $this->readInvoiceJournalSummaryService = $readInvoiceJournalSummaryService;
    }

    public function get(Invoice $invoice)   {
        switch ($invoice->invoice_type->name) {
			case InvoiceType::INVOICE_TYPE_CREDITCARD :
				// $invoice_statement = $this->read_creditcard_deposit_invoice_details ( $program_account_holder_id, $invoice_id );
                $invoice_statement = 'INVOICE_TYPE_CREDITCARD';
			break;
			case InvoiceType::INVOICE_TYPE_ON_DEMAND :
				$invoice_statement = $this->read_on_demand_invoice_details ( $invoice );
                // $invoice_statement = 'INVOICE_TYPE_ON_DEMAND';
			break;
			case InvoiceType::INVOICE_TYPE_MONTHLY :
				// I could not see any difference in these two conditions below but still copying them as it is, may be i am missing something - Arvind!
				if (!$invoice->program->program_is_invoice_for_awards ()) {
					$start_date = $invoice->date_begin;
					$end_date = $invoice->date_end;
					$invoice_statement = $this->statementService->read_statement ( $invoice->program, $start_date, $end_date );
					// dd($invoice_statement);
					$invoice_statement->journal_summary = $this->readInvoiceJournalSummaryService->get ( $invoice->program, $start_date, $end_date );
				} else {
					$start_date = $invoice->date_begin;
					$end_date = $invoice->date_end;
					$invoice_statement = $this->statementService->read_statement ( $invoice->program, $start_date, $end_date );
					$invoice_statement->journal_summary = $this->readInvoiceJournalSummaryService->get ( $invoice->program, $start_date, $end_date );
				}
				$journal_summary = $invoice_statement->journal_summary;
				// dump("Jounral Summary");
				// dump($journal_summary);
			break;
			default :
				throw new \InvalidArgumentException ( "Unsupported Invoice Type." );
		}
		$invoice->statement = $invoice_statement;
		$invoice->invoices = [
			[
				'info' => $invoice_statement, 
				'name' => $invoice->program->name
			]
		];
		$invoice->total_start_balance = $invoice_statement->start_balance;
		$invoice->total_end_balance = $invoice_statement->end_balance;
		$invoice->total_invoice_amount = $invoice_statement->invoice_amount; //not found!
		$invoice->total_payments = $invoice_statement->payments;

		$subprogramInvoices = [];

		if ($invoice->invoice_type->name == InvoiceType::INVOICE_TYPE_MONTHLY) {
			$billable_sub_programs = $this->programService->getBillableDescendants ( $invoice->program );
			if (is_array ( $billable_sub_programs ) && count ( $billable_sub_programs ) > 0) {
				foreach ( $billable_sub_programs as $key => $subProgram ) {
					// loop the journal for invoice data
					$invoice_statement = $this->statementService->read_statement ( $subProgram, $invoice->date_begin, $invoice->date_end );
					if( $invoice_statement )	{
						array_push($subprogramInvoices, ['info' => $invoice_statement,'name' => $subProgram->name]);
						$invoice->total_start_balance += $invoice_statement->start_balance;
						$invoice->total_end_balance += $invoice_statement->end_balance;
						$invoice->total_invoice_amount += $invoice_statement->invoice_amount;
						$invoice->total_payments += $invoice_statement->payments;
					}
				}
			}
		}

		if($subprogramInvoices)	{
			$invoice->invoices = array_merge($invoice->invoices, $subprogramInvoices);
		}

		// dump("JGrantTodal:" . $journal_summary['grand_total']);

		// DHF-135 - suppress all $0 invoices from being created 
		if ($invoice->invoice_type->name == InvoiceType::INVOICE_TYPE_MONTHLY && isset($journal_summary['grand_total'])) {
			$invoice->custom_invoice_amount = $journal_summary['grand_total'];
		}else{
			$invoice->custom_invoice_amount = $invoice->total_end_balance;
		}

		// dump($invoice->custom_invoice_amount);

		$this->updateInvoiceFinalAmount($invoice->id, $invoice->custom_invoice_amount);
        return $invoice;
    }

	public function updateInvoiceFinalAmount($invoiceId, $amount)
	{
		Invoice::find($invoiceId)->updated(['invoice_amount' => $amount]);
	}

	public function read_on_demand_invoice_details(Invoice $invoice) {
		return $this->read_invoice_details ( $invoice, InvoiceType::INVOICE_TYPE_ON_DEMAND );
	}
    public function read_invoice_details(Invoice $invoice, $invoice_type_name) {
		$statement = new \stdClass ();
		$statement->start_date = $invoice->date_begin;
		$statement->end_date = $invoice->date_end;
		// Starting balance is always 0 since this invoice is just for a requested amount
		$statement->start_balance = 0;
		$statement->end_balance = $statement->start_balance;
		// Read the program info to include in the statement
		// $program_info = $this->programs_model->get_program_info ( $program_account_holder_id );
		$statement->program_name = $invoice->program->name;
		$statement->program_id = $invoice->program->id;
		$statement->program_account_holder_id = $invoice->program->account_holder_id;
		$statement->payments = $this->readInvoicePaymentsService->get ( $invoice );
		$qry_statement = "
        SELECT 
            a.account_holder_id,
            atypes.name as account_type_name,
            ftypes.name as finance_type_name,
            mtypes.name as medium_type_name,
            posts.is_credit,
            c.type as currency,
            jet.type as journal_event_type,
            ifnull(je.notes, '') as notes,
            sum(posts.qty) as qty, 
            sum(posts.qty * posts.posting_amount) / sum(posts.qty) as ea, 
            sum(posts.qty * posts.posting_amount) as amount,
            exml.name as event_name,
            posts.created_at as posting_timestamp
        	FROM programs p
            INNER JOIN invoices i ON i.program_id = p.id
            INNER JOIN invoice_types t ON t.id = i.invoice_type_id and t.name = :invoice_type_name
            INNER JOIN invoice_journal_event inv_journal ON inv_journal.invoice_id = i.id 
            INNER JOIN journal_events je ON je.id = inv_journal.journal_event_id
            INNER JOIN journal_event_types jet ON jet.id = je.journal_event_type_id
            INNER JOIN postings posts ON posts.journal_event_id = je.id
            INNER JOIN accounts a ON a.id = posts.account_id
            INNER JOIN account_types atypes ON atypes.id = a.account_type_id
            INNER JOIN finance_types ftypes ON ftypes.id = a.finance_type_id
            INNER JOIN medium_types mtypes ON mtypes.id = a.medium_type_id
            INNER JOIN currencies c ON c.id = a.currency_type_id
            LEFT JOIN event_xml_data exml ON exml.id = je.event_xml_data_id
        WHERE
            p.id = :program_id
            AND i.id = :invoice_id
            AND atypes.name = 'Monies Due to Owner'
            AND posts.is_credit > 0
        GROUP BY
            posts.id
        ORDER BY
            journal_event_type, posting_timestamp ASC;
        ";
		// throw new Exception($qry_statement);
		// we execute query for reading the invoice of the program
		DB::statement("SET SQL_MODE=''"); // to prevent groupby error. see shorturl.at/qrQ07
		
        try {
			$statement_data_credits = DB::select( DB::raw($qry_statement), array(
				'invoice_type_name' => $invoice_type_name,
				'program_id' => $invoice->program->id,
				'invoice_id' => $invoice->id,
			));
		} catch (Exception $e) {
			throw new \RuntimeException ( 'Could not get information in  InvoiceService:read_invoice_details:statement_data_credits. DB query failed.', 500 );
		}

		$statement_data_credits = account_type_parser ( $statement_data_credits );
		$reversal_types = array(
			"'" . JOURNAL_EVENT_TYPES_REVERSAL_PROGRAM_PAYS_FOR_MONIES_PENDING . "'",
			"'" . JOURNAL_EVENT_TYPES_REVERSAL_PROGRAM_PAYS_FOR_DEPOSIT_FEE . "'",
		);
		$reversal_types = implode(',', $reversal_types);
		$qry_statement = "
        SELECT 
            a.account_holder_id,
            atypes.name as account_type_name,
            ftypes.name as finance_type_name,
            mtypes.name medium_type_name,
            posts.is_credit,
            c.type as currency,
            jet.type as journal_event_type,
       		ifnull(je.notes, '') as notes,
            sum(posts.qty) as qty, 
            sum(posts.qty * posts.posting_amount) / sum(posts.qty) as ea, 
            sum(posts.qty * posts.posting_amount) as amount,
            exml.name as event_name
        FROM programs p
            INNER JOIN invoices i ON i.program_id = p.id
            INNER JOIN invoice_types t ON t.id = i.invoice_type_id and t.name = :invoice_type_name
            INNER JOIN invoice_journal_event inv_journal ON inv_journal.invoice_id = i.id 
            INNER JOIN journal_events je ON je.id = inv_journal.journal_event_id
            INNER JOIN journal_event_types jet ON jet.id = je.journal_event_type_id
            INNER JOIN postings posts ON posts.journal_event_id = je.id
            INNER JOIN accounts a ON a.id = posts.account_id
            INNER JOIN account_types atypes ON atypes.id = a.account_type_id
            INNER JOIN finance_types ftypes ON ftypes.id = a.finance_type_id
            INNER JOIN medium_types mtypes ON mtypes.id = a.medium_type_id
            INNER JOIN currencies c ON c.id = a.currency_type_id
            LEFT JOIN event_xml_data exml ON exml.id = je.event_xml_data_id
        WHERE
            p.id = :program_id
            AND i.id = :invoice_id
            AND atypes.name = 'Monies Due to Owner'
			AND posts.is_credit = 0
			AND jet.type NOT IN (:reversal_types) 
        GROUP BY
            exml.name, atypes.id, posts.posting_amount, jet.type
        ORDER BY
            p.name, exml.name, posts.posting_amount, journal_event_type;
        ";
		try {
			$statement_data_debits = DB::select( DB::raw($qry_statement), array(
				'invoice_type_name' => $invoice_type_name,
				'program_id' => $invoice->program->id,
				'invoice_id' => $invoice->id,
				'reversal_types' => $reversal_types,
			));
		} catch (Exception $e) {
			throw new \RuntimeException ( 'Could not get information in  InvoiceService:read_invoice_details:statement_data_debits. DB query failed.', 500 );
		}

		$statement_data_debits = account_type_parser ( $statement_data_debits );

		// pr($statement_data_debits);
		// return;

		$qry_statement = "
        SELECT 
            a.account_holder_id,
            atypes.name as account_type_name,
            ftypes.name as finance_type_name,
            mtypes.name as medium_type_name,
            posts.is_credit,
            c.type as currency,
            jet.type as journal_event_type,
       		ifnull(je.notes, '') as notes,
            sum(posts.qty) as qty, 
            sum(posts.qty * posts.posting_amount) / sum(posts.qty) as ea, 
            sum(posts.qty * posts.posting_amount) as amount,
            exml.name as event_name
        FROM programs p
            INNER JOIN invoices i ON i.program_id = p.id
            INNER JOIN invoice_types t ON t.id = i.invoice_type_id and t.name = :invoice_type_name
            INNER JOIN invoice_journal_event inv_journal ON inv_journal.invoice_id = i.id 
            INNER JOIN journal_events je ON je.id = inv_journal.journal_event_id
            INNER JOIN journal_event_types jet ON jet.id = je.journal_event_type_id
            INNER JOIN postings posts ON posts.journal_event_id = je.id
            INNER JOIN accounts a ON a.id = posts.account_id
            INNER JOIN account_types atypes ON atypes.id = a.account_type_id
            INNER JOIN finance_types ftypes ON ftypes.id = a.finance_type_id
            INNER JOIN medium_types mtypes ON mtypes.id = a.medium_type_id
            INNER JOIN currencies c ON c.id = a.currency_type_id
            LEFT JOIN event_xml_data exml ON exml.id = je.event_xml_data_id
        WHERE
            p.id = :program_id
            AND i.id = :invoice_id
            AND atypes.name = 'Monies Due to Owner'
			AND posts.is_credit = 0
			AND jet.type IN (:reversal_types) 
        GROUP BY
            exml.name, atypes.id, posts.posting_amount, jet.type
        ORDER BY
            p.name, exml.name, posts.posting_amount, journal_event_type;
        ";

		try {
			$statement_data_reversed = DB::select( DB::raw($qry_statement), array(
				'invoice_type_name' => $invoice_type_name,
				'program_id' => $invoice->program->id,
				'invoice_id' => $invoice->id,
				'reversal_types' => $reversal_types,
			));
		} catch (Exception $e) {
			throw new \RuntimeException ( 'Could not get information in  InvoiceService:read_invoice_details:statement_data_reversed. DB query failed.', 500 );
		}

		$statement_data_reversed = account_type_parser ( $statement_data_reversed );

		// pr($statement_data_reversed);

		// sort the items by credit or debit
		$statement->credits = $statement_data_credits;
		$statement->debits = $statement_data_debits;
		$statement->reversed = $statement_data_reversed;
		// Validate that the statement is correct
		// Add each of the line items to the starting balance to see if we
		// come up with the same ending balance
		// pr($statement_data_debits);
		// exit;
		if (is_array ( $statement_data_credits ) && count ( $statement_data_credits ) > 0) {
			foreach ( $statement_data_credits as &$statement_credit_item ) {
				$statement->end_balance += number_format ( $statement_credit_item->amount, 2, '.', '' );
				// Rename the journal event type using the language file for better human readability
				if (\Lang::has( 'jet.' . $statement_credit_item->journal_event_type )) {
					$statement_credit_item->friendly_journal_event_type = __( 'jet.' . $statement_credit_item->journal_event_type );
				} else {
					$statement_credit_item->friendly_journal_event_type = $statement_credit_item->journal_event_type;
				}
				if (\Lang::has( 'jet.' . $statement_credit_item->event_name )) {
					$statement_credit_item->event_name = __( 'jet.' . $statement_credit_item->event_name );
				}
			}
		}
		if (is_array ( $statement_data_reversed ) && count ( $statement_data_reversed ) > 0) {
			foreach ( $statement_data_reversed as &$statement_reversed_item ) {
				$statement->end_balance += number_format ( $statement_reversed_item->amount, 2, '.', '' );
				// Rename the journal event type using the language file for better human readability
				if (\Lang::has( 'jet.' . $statement_reversed_item->journal_event_type )) {
					$statement_reversed_item->friendly_journal_event_type = __( 'jet_' . $statement_reversed_item->journal_event_type );
				} else {
					$statement_reversed_item->friendly_journal_event_type = $statement_reversed_item->journal_event_type;
				}
				if (\Lang::has( 'jet.' . $statement_reversed_item->event_name )) {
					$statement_reversed_item->event_name = __( 'jet.' . $statement_reversed_item->event_name );
				}
			}
		}
		if (is_array ( $statement_data_debits ) && count ( $statement_data_debits ) > 0) {
			foreach ( $statement_data_debits as &$statement_debit_item ) {
				$statement->end_balance += number_format ( $statement_debit_item->amount, 2, '.', '' );
				// Rename the journal event type using the language file for better human readability
				if (\Lang::has( 'jet.' . $statement_debit_item->journal_event_type )) {
					$statement_debit_item->friendly_journal_event_type = __( 'jet.' . $statement_debit_item->journal_event_type );
				} else {
					$statement_debit_item->friendly_journal_event_type = $statement_debit_item->journal_event_type;
				}
				if (\Lang::has( 'jet.' . $statement_debit_item->event_name )) {
					$statement_debit_item->event_name = __( 'jet.' . $statement_debit_item->event_name );
				}
			}
		}
		$statement->start_balance = number_format ( $statement->start_balance, 2, '.', '' );
		$statement->end_balance = number_format ( $statement->end_balance, 2, '.', '' );
		return $statement;
	}
}