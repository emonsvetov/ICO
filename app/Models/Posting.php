<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Posting extends Model
{
    protected $guarded = [];
    public function create( $data ) {

        //insert debit account
        $debit_posting = self::insert([
            'journal_event_id' => $data['journal_event_id'],
            'account_id' => $data['debit_account_id'],
            'posting_amount' => $data['amount'],
            'is_credit' => 0,
            'qty' => $data['quantity'],
            'medium_info_id' => $data['medium_info_id'],
            'created_at' => \Carbon\Carbon::now()
        ]);

        $credit_posting = self::insert([
            'journal_event_id' => $data['journal_event_id'],
            'account_id' => $data['credit_account_id'],
            'posting_amount' => $data['amount'],
            'is_credit' => 1,
            'qty' => $data['quantity'],
            'medium_info_id' => $data['medium_info_id'],
            'created_at' => \Carbon\Carbon::now()
        ]);

        return [
            'debit' => $debit_posting,
            'credit' => $credit_posting,
        ];
    }
}
