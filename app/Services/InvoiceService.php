<?php
namespace App\Services;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Traits\InvoiceFilters;
use App\Models\Traits\Filterable;
use App\Models\JournalEventType;
use App\Models\PaymentMethod;
use App\Models\JournalEvent;
use App\Models\FinanceType;
use App\Models\InvoiceType;
use App\Models\MediumType;
use App\Models\Currency;
use App\Models\Program;
use App\Models\Invoice;
use App\Models\Account;
use App\Models\Owner;
use DB;

class InvoiceService 
{
    use Filterable, InvoiceFilters;

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
        $query = $query->where('program_id', $program->id)->with('invoice_type');
        if( $paginate ) {
            $invoices = $query->paginate( self::$PARAMS['limit'] );
        }   else    {
            $invoices = $query->get();
        }
        // pr(DB::getQueryLog());
        return $invoices;
    }

	public function createOnDemand($data, $program) {
		// payment method
        $amount = $data['amount'];
		$payment_method_id = PaymentMethod::getPaymentMethodCheck(true);
		$deposit_fee = $program->deposit_fee / 100.0;
		$deposit_fee_amount = $deposit_fee * $amount;
		// create a new invoice
		$date_begin = $date_end = date ( 'Y-m-d' );
		$days_to_pay = isset($data['days_to_pay']) && $data['days_to_pay'] > 0 ? $data['days_to_pay'] : Invoice::DAYS_TO_PAY;
        $invoice_type_id = InvoiceType::getIdByTypeOnDemand(true);
        $program_id = $program->id;

        $user = auth()->user();

        $invoice_key = $program_id . date('ym', strtotime($date_end));

        $type_on_demand = InvoiceType::getIdByTypeOnDemand(true);
        $type_monthly = InvoiceType::getIdByTypeMonthly(true);
        $type_creditcard = InvoiceType::getIdByTypeCreditCard(true);
        $invoice = null;

        if( $invoice_type_id == $type_on_demand || $invoice_type_id == $type_creditcard )   
        {
            
        }   
        else 
        {
            $query = Invoice::where(['program_id' => $program_id, 'date_end' => $date_end, 'invoice_type_id' => $invoice_type_id]);
            if( $query->count() > 0)
            {
                $invoice = $query->select('id')->first();
            }
        }

        if( !$invoice )   {
            $count = Invoice::where(['program_id' => $program_id])
            ->where('created_at', '<=', now())
            ->count();
            $seq = $count + 1;
            // pr($days_to_pay);
            $date_due_strtotime = strtotime($date_end . " +{$days_to_pay} days");
            // pr($date_due_strtotime);

            $invoice = Invoice::create([
                'program_id' => $program_id,
                'key' => $invoice_key,
                'seq' => $seq,
                'invoice_type_id' => $invoice_type_id,
                'payment_method_id' => $payment_method_id,
                'date_begin' => $date_begin,
                'date_end' => $date_end,
                'date_due' => date ( 'Y-m-d', $date_due_strtotime )
            ]);
        }

        if( $invoice )  {
            $invoice = $this->chargeForMoniesPending($invoice, $user, $program, $amount );
            if ($deposit_fee > 0) {
            	$invoice = $this->chargeForDepositFee ($invoice, $user, $program, $deposit_fee_amount);
            }
        }

		return $invoice;
	}

    public function chargeForMoniesPending(Invoice $invoice, $user, $program, $amount)    {
        $owner = Owner::first();
        $currency_id = Currency::getIdByType(config('global.default_currency'), true);
        $owner_account_holder_id = ( int ) $owner->account_holder_id;
        $program_account_holder_id = ( int ) $program->account_holder_id;
        $prime_account_holder_id = ( int ) $user->account_holder_id;
        $monies = MediumType::getIdByName('Monies', true);
        $liability = FinanceType::getIdByName('Liability', true);
        $asset = FinanceType::getIdByName('Asset', true);
        $journal_event_type_id = JournalEventType::getIdByType( 'Charge program for monies pending', true );
        $journal_event_id = JournalEvent::insertGetId([
			'journal_event_type_id' => $journal_event_type_id,
			'prime_account_holder_id' => $prime_account_holder_id,
			'created_at' => now()
		]);
        $postings = Account::postings(
			$program_account_holder_id,
			'Monies Due to Owner',
			$asset,
			$monies,
			$program_account_holder_id,
			'Monies Pending',
			$liability,
			$monies,
			$journal_event_id,
			$amount,
			1, //qty
			null, //medium_info
			null, // medium_info_id
			$currency_id
		);
        if( $postings )   {
            $invoice->journal_events()->sync( [ $journal_event_id ], false);
        }
        return $invoice;
    }

    public function chargeForDepositFee(Invoice $invoice, $user, $program, $amount)    {
        $owner = Owner::first();
        $currency_id = Currency::getIdByType(config('global.default_currency'), true);
        $owner_account_holder_id = ( int ) $owner->account_holder_id;
        $program_account_holder_id = ( int ) $program->account_holder_id;
        $prime_account_holder_id = ( int ) $user->account_holder_id;
        $monies = MediumType::getIdByName('Monies', true);
        $liability = FinanceType::getIdByName('Liability', true);
        $asset = FinanceType::getIdByName('Asset', true);
        $journal_event_type_id = JournalEventType::getIdByType( 'Charge program for deposit fee', true );
        $journal_event_id = JournalEvent::insertGetId([
			'journal_event_type_id' => $journal_event_type_id,
			'prime_account_holder_id' => $prime_account_holder_id,
			'created_at' => now()
		]);
        $postings = Account::postings(
			$program_account_holder_id,
			'Monies Due to Owner',
			$asset,
			$monies,
			$program_account_holder_id,
			'Monies Fees',
			$liability,
			$monies,
			$journal_event_id,
			$amount,
			1, //qty
			null, //medium_info
			null, // medium_info_id
			$currency_id
		);
        if( $postings )   {
            $invoice->journal_events()->sync( [ $journal_event_id ], false);
        }
        return $invoice;
    }

    public function getInvoice(Invoice $invoice)   {
        if( !$invoice->exists() ) return null;
        $invoice->load(['program', 'program.address', 'invoice_type', 'journal_events']);
        $invoice_statement = $this->getInvoiceStatement($invoice);
		$invoice->invoices = [
			[
				'info' => $invoice_statement, 
				'name' => $invoice->program->name
			]
		];
		$invoice->total_start_balance = $invoice_statement->start_balance;
		$invoice->total_end_balance = $invoice_statement->end_balance;
		// $invoice->total_invoice_amount = $invoice_statement->invoice_amount; //not found!
		$invoice->total_payments = $invoice_statement->payments;
		if ($invoice->invoice_type->name == InvoiceType::INVOICE_TYPE_MONTHLY) {
			// TODO - Dont remove
			// $billable_sub_programs = $this->programs_model->read_list_billable_descendants ( $program_account_holder_id );
			// if (is_array ( $billable_sub_programs ) && count ( $billable_sub_programs ) > 0) {
			// 	foreach ( $billable_sub_programs as $key => $program ) {
			// 		// loop the journal for invoice data
			// 		$invoice_statement = $this->read_statement ( ( int ) $program->account_holder_id, $invoice_data->date_begin, $invoice_data->date_end );
			// 		$data ['invoice'] [] = array (
			// 				'info' => $invoice_statement,
			// 				'name' => $program->name 
			// 		);
			// 		$data ['total_start_balance'] += $invoice_statement->start_balance;
			// 		$data ['total_end_balance'] += $invoice_statement->end_balance;
			// 		$data ['total_invoice_amount'] += $invoice_statement->invoice_amount;
			// 		$data ['total_payments'] += $invoice_statement->payments;
			// 	}
			// }
		}
        return $invoice;
    }

    public function getInvoiceStatement(Invoice $invoice)   {
        // pr($invoice->invoice_type->name);
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
				// if ($this->programs_model->program_is_invoice_for_awards ( $program_account_holder_id )) {
				// 	$start_date = $invoice_data->date_begin;
				// 	$end_date = $invoice_data->date_end;
				// 	$invoice_statement = $this->read_statement ( $program_account_holder_id, $start_date, $end_date );
				// 	$invoice_statement->journal_summary = $this->read_invoice_journal_summary ( $program_account_holder_id, $start_date, $end_date );
				// } else {
				// 	$start_date = $invoice_data->date_begin;
				// 	$end_date = $invoice_data->date_end;
				// 	$invoice_statement = $this->read_statement ( $program_account_holder_id, $start_date, $end_date );
				// 	$invoice_statement->journal_summary = $this->read_invoice_journal_summary ( $program_account_holder_id, $start_date, $end_date );
				// }
				// $journal_summary = $invoice_statement->journal_summary;
                $invoice_statement = 'INVOICE_TYPE_MONTHLY';
			break;
			default :
				throw new InvalidArgumentException ( "Unsupported Invoice Type." );
		}
        return $invoice_statement;
    }

	public function read_on_demand_invoice_details(Invoice $invoice) {
		return $this->read_invoice_details ( $invoice, InvoiceType::INVOICE_TYPE_ON_DEMAND );
	}

    public function read_invoice_details(Invoice $invoice, $invoice_type_name) {
		// assert_is_positive_int ( "program_account_holder_id", $program_account_holder_id );
		// assert_is_positive_int ( "invoice_id", $invoice_id );
		// $invoice = $this->read_invoice ( $program_account_holder_id, $invoice_id );
		// assert_date_matches_format ( "invoice->date_begin", $invoice->date_begin, "Y-m-d" );
		// assert_date_matches_format ( "invoice->date_end", $invoice->date_end, "Y-m-d" );
		$statement = new \stdClass ();
		$statement->start_date = $invoice->date_begin;
		$statement->end_date = $invoice->date_end;
		// Starting balance is always 0 since this invoice is just for a requested amount
		$statement->start_balance = 0;
		$statement->end_balance = $statement->start_balance;
		// Read the program info to include in the statement
		// $program_info = $this->programs_model->get_program_info ( $program_account_holder_id );
		$statement->program_name = $invoice->program->name;
		$statement->program_account_holder_id = $invoice->program->account_holder_id;
		$statement->payments = $this->read_invoice_payments ( $invoice );
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

	public function read_invoice_payments(Invoice $invoice) {
		$sql = "
		select
			invoices.id as invoice_id
			, a.account_holder_id as program_account_holder_id
			, account_types.name
			, jet.type as journal_event_type
			, je.created_at
			, je.notes
			, postings.is_credit
			, p.name as program_name
			, sum(postings.posting_amount * postings.qty) as amount
			, je.id as journal_event_id
		from
			invoices
			join invoice_journal_event ijet on (invoices.id = ijet.invoice_id)
			join journal_events je on (ijet.journal_event_id = je.id)
			join journal_event_types jet on (je.journal_event_type_id = jet.id)
			join postings on (postings.journal_event_id = je.id)
			join accounts a on (a.id = postings.account_id)
			join account_types on (a.account_type_id = account_types.id)
			join programs p on (p.account_holder_id = a.account_holder_id)
			left join journal_events reversals on (je.id = reversals.parent_id)
		where
			invoices.id = :invoice_id
			and account_types.name = 'Monies Due to Owner'
			and postings.is_credit = 1
			and reversals.id is null
	    group by
	       je.id
    	";

        DB::statement("SET SQL_MODE=''"); // to prevent groupby error. see shorturl.at/qrQ07
		
        try {
			$result = DB::select( DB::raw($sql), array(
				'invoice_id' => $invoice->id,
			));
		} catch (Exception $e) {
			throw new \RuntimeException ( 'Could not get information in  InvoiceService:read_invoice_payments. DB query failed.', 500 );
		}
		return $result;
	}
}
