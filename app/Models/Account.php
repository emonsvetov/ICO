<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\AccountType;
use App\Models\Currency;
use App\Models\Posting;

class Account extends Model
{
    protected $guarded = [];

	private static $account_type_fields = [
        'finance_type',
        'medium_type',
        'account_type',
        'currency_type'
    ];

    public function getIdByColumns( $args = [], $insert = true)    {
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

    public function postings(
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

        // pr("debit_account_type_name");
        // pr($debit_account_type_name);

        $debit_account_type_id = AccountType::getIdByName($debit_account_type_name, true);
        // pr("debit_account_type_id");
        // pr($debit_account_type_id);
        // Debit Account
        $debit_account_id = self::getIdByColumns([
            'account_type_id' => $debit_account_type_id,
            'account_holder_id' => $debit_account_holder_id,
            'finance_type_id' => $debit_finance_type_id,
            'medium_type_id' => $debit_medium_type_id,
            'currency_type_id' => $currency_id
        ]);

        // pr("debit_account_id");
        // pr($debit_account_id);

        // pr("credit_account_type_name");
        // pr($credit_account_type_name);

        $credit_account_type_id = AccountType::getIdByName($credit_account_type_name, true);
        // pr("credit_account_type_id");
        // pr($credit_account_type_id);
        // Credit Account
        $credit_account_id = self::getIdByColumns([
            'account_type_id' => $credit_account_type_id,
            'account_holder_id' => $credit_account_holder_id,
            'finance_type_id' => $credit_finance_type_id,
            'medium_type_id' => $credit_medium_type_id,
            'currency_id' => $currency_id
        ]);

        // pr("credit_account_id");
        // pr($credit_account_id);

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

        return $result;
    }

	public function create_multi_accounts($account_holder_id = 0, $accounts = array()) {
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
}
