<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\AccountType;
use App\Models\Posting;

class Account extends Model
{
    protected $guarded = [];

    public function getIdByColumns( $args = [], $insert = true)    {
        $fields = [];
        $_fields = ['account_type_id', 'account_holder_id', 'finance_type_id', 'medium_type_id'];
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
                $fields['currency_type_id'] = 1 ;
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
        $medium_fields,
        $medium_values,
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

        $result['posting'] = Posting::create([
            'journal_event_id' => $journal_event_id,
            'debit_account_id' => $debit_account_id,
            'credit_account_id' => $credit_account_id,
            'amount' => $amount,
            'quantity' => $quantity,
            'medium_fields' => $medium_fields,
            'medium_values' => $medium_values,
            'medium_info_id' => $medium_info_id,
            'debit_medium_type_id' => $debit_medium_type_id,
        ]);

        return $result;
    }
}
