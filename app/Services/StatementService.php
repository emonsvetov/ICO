<?php
namespace App\Services;

use App\Services\Statement\StatementObject;
use App\Models\JournalEventType;
use App\Services\InvoiceService;
use App\Models\Program;
use App\Models\Posting;
use App\Models\Role;
use App\Models\User;
use DB;

class StatementService
{
    public function get(Program $program, array $filters = null)   {
        $statement = new \stdClass();

        $start_date = $filters['start_date'];
        $end_date = $filters['end_date'];

        $program->load('address');
        $statement->address_info = $program->address;

        $invoice_statement = $this->read_statement($program, $start_date, $end_date);

        $statement->start_date = $start_date;
        $statement->end_date = $end_date;
        $statement->previous_balance_date = date('Y-m-d', strtotime('-1 day', strtotime($start_date)));

        $statement->total_start_balance = $invoice_statement->start_balance;
        $statement->total_end_balance = $invoice_statement->end_balance;
        $statement->statement[0]['info'] = $invoice_statement;

        $descendants = $program->descendants()->depthFirst()->get();

        foreach($descendants as $descendant)    {
            $invoice_statement = $this->read_statement($descendant, $start_date, $end_date );
            $statement->statement[] = array (
                    'info' => $invoice_statement 
            );
            // $data['statement'][$key + 1]['name'] = $program->name;
            $statement->total_start_balance += $invoice_statement->start_balance;
            $statement->total_end_balance += $invoice_statement->end_balance;
        }

        return $statement;
    }

	/** read_statement
	 * 
	 * @param int $program       
	 * @param date $start_date        
	 * @param date $end_date         */
	public function read_statement(Program $program, $start_date, $end_date) {
		$statement = new StatementObject ();
		$statement->program_account_holder_id = $program->account_holder_id;
		$statement->start_date = $start_date;
		$statement->end_date = $end_date;
		// Get the starting balance of the statement requested, used for doing the itemization math
		$statement->start_balance = $this->read_financial_balance_less_than_date ( $program, $start_date );
		// Get the ending balance of the statement, used to validate that the itemization math was correct
		// Add 1 day since the query will get everything less than this day and we need to include the end date of the statement
		$statement->end_balance = $this->read_financial_balance_less_than_date ( $program, date ( 'Y-m-d', strtotime ( '+1 day', strtotime ( $end_date ) ) ) );
		// $statement->invoice_amount= $this->read_billed_amount_between($program_account_holder_id, $start_date, $end_date); //commented out in current system
		$statement->payments = $this->read_payments_between ( $program, $start_date, $end_date );
		// Read the program info to include in the statement
		$statement->program_name = $program->name;

        $invoiceForAwards = $program->program_is_invoice_for_awards();

        //Prepare Credit Statement

		$sql = "
        SELECT 
            a.account_holder_id,
            atypes.name AS account_type_name,
            ftypes.name,
            mtypes.name,
            posts.is_credit,
            c.type as currency,
            jet.type as journal_event_type,
            sum(posts.qty) as qty, 
            sum(posts.qty * posts.posting_amount) / sum(posts.qty) as ea, 
            sum(posts.qty * posts.posting_amount) as amount,
            exml.name as event_name,
            posts.created_at
        		
        FROM " . PROGRAMS . " p
            INNER JOIN " . ACCOUNTS . " a ON a.account_holder_id = p.account_holder_id
            INNER JOIN " . ACCOUNT_TYPES . " atypes ON atypes.id = a.account_type_id
            INNER JOIN " . FINANCE_TYPES . " ftypes ON ftypes.id = a.finance_type_id
            INNER JOIN " . MEDIUM_TYPES . " mtypes ON mtypes.id = a.medium_type_id
            INNER JOIN " . CURRENCIES . " c ON c.id = a.currency_type_id
            INNER JOIN " . POSTINGS . " posts ON posts.account_id = a.id
            INNER JOIN " . JOURNAL_EVENTS . " je ON je.id = posts.journal_event_id
            INNER JOIN " . JOURNAL_EVENT_TYPES . " jet ON jet.id = je.journal_event_type_id
            LEFT JOIN " . EVENT_XML_DATA . " exml ON exml.id = je.event_xml_data_id
            LEFT JOIN " . INVOICE_JOURNAL_EVENTS . " invoicej on invoicej.journal_event_id = je.id 
        WHERE
            p.id = :program_id
            AND atypes.name = 'Monies Due to Owner'
            AND (posts.created_at >= :start_date
                and posts.created_at < DATE_ADD(:end_date, INTERVAL 1 DAY)
            )
            AND
            posts.is_credit > 0
        ";
		if ( !$invoiceForAwards ) {
			$sql = $sql . "
	            AND ( invoicej.invoice_id is null
               		or (jet.type not in (
							'" . JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_MONIES_PENDING . "'
							, '" . JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_DEPOSIT_FEE . "'
							, '" . JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_CONVENIENCE_FEE . "'		
        				) 
        			and 
        				invoicej.invoice_id is not null
        			) 
            	)
        	";
		}
		$sql = $sql . "
        GROUP BY
            posts.id
        ORDER BY
            journal_event_type, posts.created_at ASC;
        ";

        DB::statement("SET SQL_MODE=''"); // to prevent groupby error. see shorturl.at/qrQ07
        try {
            $result = DB::select( DB::raw($sql), array(
                'program_id' =>  $program->id,
                'start_date' =>  $start_date,
                'end_date' => $end_date
            ));
            $statement_data_credits = $result;
        } catch (Exception $e) {
            throw new \RuntimeException ( 'Could not get data in  StatementService:read_financial_balance_less_than_date. DB query failed.', 500 );
        }

		$statement_data_credits = account_type_parser ( $statement_data_credits );

        //Prepare Debig Statement

		$sql = "
        SELECT 
            a.account_holder_id,
            atypes.name AS account_type_name,
            ftypes.name,
            mtypes.name,
            posts.is_credit,
            c.type as currency,
            jet.type as journal_event_type,
            sum(posts.qty) as qty, 
            sum(posts.qty * posts.posting_amount) / sum(posts.qty) as ea, 
            sum(posts.qty * posts.posting_amount) as amount,
            exml.name as event_name
        FROM " . PROGRAMS . " p
            INNER JOIN " . ACCOUNTS . " a ON a.account_holder_id = p.account_holder_id
            INNER JOIN " . ACCOUNT_TYPES . " atypes ON atypes.id = a.account_type_id
            INNER JOIN " . FINANCE_TYPES . " ftypes ON ftypes.id = a.finance_type_id
            INNER JOIN " . MEDIUM_TYPES . " mtypes ON mtypes.id = a.medium_type_id
            INNER JOIN " . CURRENCIES . " c ON c.id = a.currency_type_id
            INNER JOIN " . POSTINGS . " posts ON posts.account_id = a.id
            INNER JOIN " . JOURNAL_EVENTS . " je ON je.id = posts.journal_event_id
            INNER JOIN " . JOURNAL_EVENT_TYPES . " jet ON jet.id = je.journal_event_type_id
            LEFT JOIN " . EVENT_XML_DATA . " exml ON exml.id = je.event_xml_data_id
            LEFT JOIN " . INVOICE_JOURNAL_EVENTS . " invoicej on invoicej.journal_event_id = je.id 
        WHERE
            p.id = :program_id
            AND atypes.name = 'Monies Due to Owner'
            AND (posts.created_at >= :start_date
                and posts.created_at < DATE_ADD(:end_date, INTERVAL 1 DAY)
            )
            AND
            posts.is_credit = 0
        ";
		if ( !$invoiceForAwards ) {
			$sql = $sql . "
            AND ( invoicej.invoice_id is null
               	or (
        			jet.type not in (
							'" . JournalEventType::JOURNAL_EVENT_TYPES_CHARGE_MONIES_PENDING . "'
							,'" . JournalEventType::JOURNAL_EVENT_TYPES_CHARGE_DEPOSIT_FEE . "'
							,'" . JournalEventType::JOURNAL_EVENT_TYPES_CHARGE_CONVENIENCE_FEE . "'
        			)
        		and 
        			invoicej.invoice_id is not null
        		)
            )
        ";
		}
		$sql = $sql . "
        GROUP BY
            exml.name, atypes.id, posts.posting_amount, jet.type
        ORDER BY
            p.name, exml.name, posts.posting_amount, journal_event_type;
        ";

		DB::statement("SET SQL_MODE=''"); // to prevent groupby error. see shorturl.at/qrQ07
        try {
            $result = DB::select( DB::raw($sql), array(
                'program_id' =>  $program->id,
                'start_date' =>  $start_date,
                'end_date' => $end_date
            ));
            $statement_data_debits = $result;
        } catch (Exception $e) {
            throw new \RuntimeException ( 'Could not get data in  StatementService:read_financial_balance_less_than_date. DB query failed.', 500 );
        }

		$statement_data_debits = account_type_parser ( $statement_data_debits );
        $statement->debits = $statement_data_debits;

        /*
            // This section doing nothing in the current code as well so commented out
            $top_level_program = $program->getRoot(['id', 'name']);
            
            if($invoiceForAwards){
                $sql_ext = "{$program_account_holder_id}";
            }else{
                $sql_ext = "SELECT p.descendant FROM program_paths p WHERE p.ancestor =  {$top_level_program}";
            }
        */

		$sql = "
            SELECT 
            COALESCE(SUM(mi.cost_basis), 0) AS cost_basis,
            COALESCE(SUM(mi.redemption_value - mi.sku_value), 0) AS premiumamount,
                jet.type AS journal_event_type,
                a.account_holder_id AS account_holder_id,
                atypes.name AS account_type_name
            FROM
                " . POSTINGS . " AS posts
                    LEFT JOIN
                " . ACCOUNTS . " a ON posts.account_id = a.id
                    LEFT JOIN
                " . ACCOUNT_TYPES . " atypes ON atypes.id = a.account_type_id
                    INNER JOIN
                " . JOURNAL_EVENTS . " je ON je.id = posts.journal_event_id
                    INNER JOIN
                " . JOURNAL_EVENT_TYPES . " jet ON jet.id = je.journal_event_type_id
                    INNER JOIN
                " . POSTINGS . " merchant_posts ON merchant_posts.journal_event_id = je.id
                    INNER JOIN
                " . MEDIUM_INFO . " mi ON mi.id = merchant_posts.medium_info_id
                    INNER JOIN
                " . ACCOUNTS . " merchant_account ON merchant_account.id = merchant_posts.account_id
                    INNER JOIN
                " . MERCHANTS . " m ON m.account_holder_id = merchant_account.account_holder_id
            WHERE 
                posts.is_credit = 1 
                AND atypes.name IN ('Points Redeemed','Monies Redeemed','Monies Available','Monies Due to Owner') 
                AND jet.type IN ('Redeem points for gift codes','Redeem points for international shopping','Redeem monies for gift codes') 
                AND a.account_holder_id = :program_account_holder_id
                AND posts.created_at >= :start_date
                AND posts.created_at < DATE_ADD(:end_date, INTERVAL 1 DAY)
            GROUP BY a.account_holder_id,atypes.id,jet.id;
        ";

        DB::statement("SET SQL_MODE=''"); // to prevent groupby error. see shorturl.at/qrQ07
        try {
            $result = DB::select( DB::raw($sql), array(
                'program_account_holder_id' =>  $program->account_holder_id,
                'start_date' =>  $start_date,
                'end_date' => $end_date
            ));
            $premiumRow = current($result);
        } catch (Exception $e) {
            throw new \RuntimeException ( 'Could not get data in  StatementService:read_financial_balance_less_than_date. DB query failed.', 500 );
        }

		if($program->air_premium_cost_to_program){
			$premium = new stdClass();
			 
			$premium->account_holder_id = $premiumRow->account_holder_id;
			$premium->account_type_name = 'Monies Due to Owner';
            $premium->finance_type_name = 'Asset';
            $premium->medium_type_name = 'Monies';
            $premium->is_credit = 0;
            $premium->currency = 'USD';
            $premium->journal_event_type = $premiumRow->journal_event_type;
            $premium->qty = 1;
            $premium->ea = $premiumRow->premiumamount;
            $premium->amount = $premiumRow->premiumamount * -1;
            $premium->event_name = 'Premium cost to program';
            $premium->friendly_journal_event_type = 'Premium cost to program';

            $statement_data_debits[] = $premium;
            $premiumBalance = $premiumRow->premiumamount;
			$statement->start_balance += $premiumBalance;
		}
		
		$statement->credits = $statement_data_credits;
		$statement->debits = $statement_data_debits;
		// Validate that the statement is correct
		// Add each of the line items to the starting balance to see if we
		// come up with the same ending balance
		$start_balance = $statement->start_balance;
		if (is_array ( $statement_data_credits ) && count ( $statement_data_credits ) > 0) {
			foreach ( $statement_data_credits as &$statement_credit_item ) {
				$start_balance += $statement_credit_item->amount;
				// Rename the journal event type using the language file for better human readability
				if (\Lang::has( 'jet.' . $statement_credit_item->journal_event_type )) {
                    $statement_credit_item->friendly_journal_event_type = __( 'jet_' . $statement_credit_item->journal_event_type );
				} else {
					$statement_credit_item->friendly_journal_event_type = $statement_credit_item->journal_event_type;
				}
				if (\Lang::has( 'jet_' . $statement_credit_item->event_name )) {
					$statement_credit_item->event_name = __( 'jet_' . $statement_credit_item->event_name );
				}
			}
		}
		if (is_array ( $statement_data_debits ) && count ( $statement_data_debits ) > 0) {
			foreach ( $statement_data_debits as &$statement_debit_item ) {
				$start_balance += $statement_debit_item->amount;
				// Rename the journal event type using the language file for better human readability
				if (\Lang::has( 'jet_' . $statement_debit_item->journal_event_type )) {
					$statement_debit_item->friendly_journal_event_type = __('jet_' . $statement_debit_item->journal_event_type);
				} else {
					$statement_debit_item->friendly_journal_event_type = $statement_debit_item->journal_event_type;
				}
				if (\Lang::has( 'jet_' . $statement_debit_item->event_name )) {
					$statement_debit_item->event_name = __( 'jet_' . $statement_debit_item->event_name );
				}
			}
		}
		if (round ( $start_balance, 4 ) != round ( $statement->end_balance, 4 )) {
			throw new \RuntimeException ( 'Internal query failed, value mismatch, please contact API administrator.', 500 );
		}
		return $statement;
	
	}

    public function read_financial_balance_less_than_date($program, $end_date) {
		$sql = "
            SELECT
                SUM(IF(" . POSTINGS . ".is_credit = 0, -1 * (" . POSTINGS . ".qty * " . POSTINGS . ".posting_amount),  (" . POSTINGS . ".qty * " . POSTINGS . ".posting_amount))) as balance
            FROM
                " . ACCOUNTS . "
            INNER JOIN
                " . ACCOUNT_TYPES . " ON " . ACCOUNT_TYPES . ".id = " . ACCOUNTS . ".account_type_id
            INNER JOIN
                " . FINANCE_TYPES . " ON " . FINANCE_TYPES . ".id = " . ACCOUNTS . ".finance_type_id
            INNER JOIN
                " . MEDIUM_TYPES . " ON " . MEDIUM_TYPES . ".id = " . ACCOUNTS . ".medium_type_id
            INNER JOIN
                " . CURRENCIES . " ON " . CURRENCIES . ".id = " . ACCOUNTS . "." . CURRENCY . "_type_id
            INNER JOIN
                " . POSTINGS . " ON " . POSTINGS . ".account_id = " . ACCOUNTS . ".id
            INNER JOIN
                " . JOURNAL_EVENTS . " ON " . JOURNAL_EVENTS . ".id = " . POSTINGS . ".journal_event_id
            INNER JOIN
                " . JOURNAL_EVENT_TYPES . " ON " . JOURNAL_EVENT_TYPES . ".id = " . JOURNAL_EVENTS . ".journal_event_type_id
            INNER JOIN
                " . PROGRAMS . " ON " . PROGRAMS . ".account_holder_id = " . ACCOUNTS . ".account_holder_id
			LEFT JOIN " . INVOICE_JOURNAL_EVENTS . " invoicej on invoicej.journal_event_id = " . JOURNAL_EVENTS . ".id
			
            WHERE
                " . ACCOUNTS . ".account_holder_id = :program_account_holder_id
				AND " . ACCOUNT_TYPES . ".name = 'Monies Due to Owner'
                AND " . POSTINGS . ".created_at < :created_at
        ";
		if (! $program->program_is_invoice_for_awards ()) {
			$sql = $sql . "
                AND ( invoicej.invoice_id is null

					or (" . JOURNAL_EVENT_TYPES . ".type not in (
							'" . JournalEventType::JOURNAL_EVENT_TYPES_CHARGE_MONIES_PENDING . "'
							,'" . JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_MONIES_PENDING . "'
							,'" . JournalEventType::JOURNAL_EVENT_TYPES_CHARGE_DEPOSIT_FEE . "'
							,'" . JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_DEPOSIT_FEE . "'
							,'" . JournalEventType::JOURNAL_EVENT_TYPES_CHARGE_CONVENIENCE_FEE . "'
							,'" . JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_CONVENIENCE_FEE . "'
						)
						and
							invoicej.invoice_id is not null) 
                	)
        	";
		}
		$sql = $sql . "
	        ORDER BY " . ACCOUNTS . ".id;";

        DB::statement("SET SQL_MODE=''"); // to prevent groupby error. see shorturl.at/qrQ07
        try {
            $result = DB::select( DB::raw($sql), array(
                'program_account_holder_id' =>  $program->account_holder_id,
                'created_at' => $end_date
            ));
            return current($result)->balance;
        } catch (Exception $e) {
            throw new \RuntimeException ( 'Could not get data in  StatementService:read_financial_balance_less_than_date. DB query failed.', 500 );
        }
	}

	public function read_payments_between($program, $start_date, $end_date) {
		$sql = "
            SELECT
                SUM(IF(" . POSTINGS . ".is_credit = 0, 0 * (" . POSTINGS . ".qty * " . POSTINGS . ".posting_amount), -1 * (" . POSTINGS . ".qty * " . POSTINGS . ".posting_amount))) as balance
            FROM
                " . ACCOUNTS . "
            INNER JOIN
                " . ACCOUNT_TYPES . " ON " . ACCOUNT_TYPES . ".id = " . ACCOUNTS . ".account_type_id
            INNER JOIN
                " . FINANCE_TYPES . " ON " . FINANCE_TYPES . ".id = " . ACCOUNTS . ".finance_type_id
            INNER JOIN
                " . MEDIUM_TYPES . " ON " . MEDIUM_TYPES . ".id = " . ACCOUNTS . ".medium_type_id
            INNER JOIN
                " . CURRENCIES . " ON " . CURRENCIES . ".id = " . ACCOUNTS . "." . CURRENCY . "_type_id
            INNER JOIN
                " . POSTINGS . " ON " . POSTINGS . ".account_id = " . ACCOUNTS . ".id
            INNER JOIN
                " . JOURNAL_EVENTS . " ON " . JOURNAL_EVENTS . ".id = " . POSTINGS . ".journal_event_id
            INNER JOIN
                " . JOURNAL_EVENT_TYPES . " ON " . JOURNAL_EVENT_TYPES . ".id = " . JOURNAL_EVENTS . ".journal_event_type_id
            INNER JOIN
                " . PROGRAMS . " ON " . PROGRAMS . ".account_holder_id = " . ACCOUNTS . ".account_holder_id
			LEFT JOIN " . INVOICE_JOURNAL_EVENTS . " invoicej on invoicej.journal_event_id = " . JOURNAL_EVENTS . ".id
	
            WHERE
                " . ACCOUNTS . ".account_holder_id = :program_account_holder_id
	                AND " . ACCOUNT_TYPES . ".name = 'Monies Due to Owner'
                AND " . POSTINGS . ".created_at >= DATE(:start_date)
	                AND " . POSTINGS . ".created_at < DATE_ADD(DATE(:end_date), INTERVAL 1 DAY)
	                ";
		if (! $program->program_is_invoice_for_awards ()) {
			$sql = $sql . "
                AND ( invoicej.invoice_id is null
	
					or (" . JOURNAL_EVENT_TYPES . ".type not in (
							'" . JournalEventType::JOURNAL_EVENT_TYPES_CHARGE_MONIES_PENDING . "'
							,'" . JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_MONIES_PENDING . "'
							,'" . JournalEventType::JOURNAL_EVENT_TYPES_CHARGE_DEPOSIT_FEE . "'
							,'" . JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_DEPOSIT_FEE . "'
							,'" . JournalEventType::JOURNAL_EVENT_TYPES_CHARGE_CONVENIENCE_FEE . "'
							,'" . JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_CONVENIENCE_FEE . "'
						)
						and
						invoicej.invoice_id is not null)
                	)
        	";
		}
		$sql = $sql . "
	        ORDER BY " . ACCOUNTS . ".id;";

        DB::statement("SET SQL_MODE=''"); // to prevent groupby error. see shorturl.at/qrQ07
        try {
            $result = DB::select( DB::raw($sql), array(
                'program_account_holder_id' =>  $program->account_holder_id,
                'start_date' =>  $start_date,
                'end_date' => $end_date
            ));
            return current($result)->balance;
        } catch (Exception $e) {
            throw new \RuntimeException ( 'Could not get data in  StatementService:read_financial_balance_less_than_date. DB query failed.', 500 );
        }
	
	}
}
