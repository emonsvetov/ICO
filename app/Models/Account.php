<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\AccountType;
use App\Models\Currency;
use App\Models\Posting;

class Account extends BaseModel
{
    protected $guarded = [];

	private static $account_type_fields = [
        'finance_type',
        'medium_type',
        'account_type',
        'currency_type'
    ];

    public static function getIdByColumns( $args = [], $insert = true)    {
        $fields = [];
        $_fields = ['account_holder_id', 'account_type_id', 'finance_type_id', 'medium_type_id', 'currency_type_id'];
        foreach( $_fields as $field )    {
            if( isset($args[$field]) && (int) $args[$field] ) {
                $fields[$field] = (int) $args[$field];
            }
        }
        if( !$fields ) return;
        $first = self::where( $fields )->first();
        if( $first ) {
            // pr("Found");
            // pr( $fields );
            return $first->id;
        }
        if( $insert )  {
            if( !isset( $fields['currency_type_id'] ))  {
                $fields['currency_type_id'] = Currency::getIdByType(config('global.default_currency'), true) ;
            }
            // pr("Inserting");
            // pr( $fields );
            return self::insertGetId( $fields );
        }
    }

    public static function postings(
        $debit_account_holder_id,
        $debit_account_type_name,
        $debit_finance_type_id,
        $debit_medium_type_id,
        $credit_account_holder_id,
        $credit_account_type_name,
        $credit_finance_type_id,
        $credit_medium_type_id,
        $journal_event_id,
        $amount,
        $quantity,
        $medium_info,
        $medium_info_id,
        $currency_id
    ) {

        $result = null;

        // GetSet Accounts
        $debit_account_type_id = AccountType::getIdByName($debit_account_type_name, true);

        // Debit Account - GetSet/Create Accounts
        $debit_account_id = self::getIdByColumns([
            'account_type_id' => $debit_account_type_id,
            'account_holder_id' => $debit_account_holder_id,
            'finance_type_id' => $debit_finance_type_id,
            'medium_type_id' => $debit_medium_type_id,
            'currency_type_id' => $currency_id
        ], true);

        $credit_account_type_id = AccountType::getIdByName($credit_account_type_name, true);

        // Credit Account - GetSet/Create Accounts
        $credit_account_id = self::getIdByColumns([
            'account_type_id' => $credit_account_type_id,
            'account_holder_id' => $credit_account_holder_id,
            'finance_type_id' => $credit_finance_type_id,
            'medium_type_id' => $credit_medium_type_id,
            'currency_id' => $currency_id
        ], true);

        $result['postings'] = Posting::createPostings([
            'journal_event_id' => $journal_event_id,
            'debit_account_id' => $debit_account_id,
            'credit_account_id' => $credit_account_id,
            'amount' => $amount,
            'quantity' => $quantity,
            'medium_info' => $medium_info,
            'medium_info_id' => $medium_info_id,
            'debit_medium_type_id' => $debit_medium_type_id,
        ]);

        $result['success'] = true;

        return $result;
    }

	public static function create_multi_accounts($account_holder_id = 0, $accounts = array()) {
		$result = false;
		foreach ( $accounts as $i => $info ) {
            if( blank($info) ) continue;
			if (! isset ( $info ['currency_type'] ) || ( int ) $info ['currency_type'] < 1) {
				$info ['currency_type'] = Currency::getIdByType(config('global.default_currency'), true);
			} else {
				$info ['currency_type'] = ( int ) $info ['currency_type'];
			}
			// iterate each account type fields to verify that each field is defined and has a valid value
			foreach ( self::$account_type_fields as $key ) {
				if( !isset( $info[$key] ) || blank($info[$key]) ) {
                    return ['errors' => "Invalid key value for {$key}"];
                    exit;
                }
			}

            $account_type_id = AccountType::getIdByName($info['account_type'], true);

            $attributes = [
                'account_holder_id' => $account_holder_id,
                'account_type_id' => $account_type_id,
                'finance_type_id' => $info['finance_type'],
                'medium_type_id' => $info ['medium_type'],
                'currency_type_id' => $info ['currency_type'],
            ];

			$result [] = self::getIdByColumns ( $attributes, true );
		}
		return $result;

	}

    public static function read_available_balance_for_program( $program ) {
        $account_type = AccountType::ACCOUNT_TYPE_MONIES_AVAILABLE;
		$journal_event_types = array (); // leave $journal_event_types empty to get all journal events
		if ( $program->program_is_invoice_for_awards() ) {
			$account_type = AccountType::ACCOUNT_TYPE_POINTS_AVAILABLE;
		}
		return self::_read_balance ( $program->account_holder_id, $account_type, $journal_event_types );
    }

	private static function _read_balance($account_holder_id, $account_type, $journal_events = []) {
		$credits = JournalEvent::read_sum_postings_by_account_and_journal_events ( ( int ) $account_holder_id, $account_type, $journal_events, 1 );
		$debits = JournalEvent::read_sum_postings_by_account_and_journal_events ( ( int ) $account_holder_id, $account_type, $journal_events, 0 );
		$bal = ( float ) (number_format ( ($credits->total - $debits->total), 2, '.', '' ));
		return $bal;
	}
}
