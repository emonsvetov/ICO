<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

use App\Models\Giftcode;

class Posting extends Model
{
    protected $guarded = [];
    public function createPostings( $data ) {

        // pr($data);

        $medium_info_id = null;
        $debit_medium_type_name = "";
        $debitAccount = Account::find($data['debit_account_id']);
        // pr($debitAccount->toArray());
        if( $debitAccount ) {
            $mediumType = MediumType::find( $debitAccount->medium_type_id );
            if( $mediumType )   {
                $debit_medium_type_name = $mediumType->name;
            }
        }

        // pr($debit_medium_type_name);

        if( $debit_medium_type_name == "Gift Codes")  {
            if( sizeof($data['medium_info']) > 0 )  {
                $giftcodeCreated = Giftcode::create( $data['medium_info']  );
                if( $giftcodeCreated )   {
                    $medium_info_id = $giftcodeCreated->id;
                }
            }   else if( (int) $data['medium_info_id'] )   {
                $mediumInfo = Giftcode::find( $data['medium_info_id'] );
                if( $mediumInfo )   {
                    $medium_info_id = $mediumInfo->id;
                }
            }
        }

        // pr($medium_info_id);

        // exit;

        //insert debit account
        $debit_posting = self::create([
            'journal_event_id' => $data['journal_event_id'],
            'account_id' => $data['debit_account_id'],
            'posting_amount' => $data['amount'],
            'is_credit' => 0,
            'qty' => $data['quantity'],
            'medium_info_id' => $medium_info_id,
            'created_at' => now()
        ]);

        $credit_posting = self::create([
            'journal_event_id' => $data['journal_event_id'],
            'account_id' => $data['credit_account_id'],
            'posting_amount' => $data['amount'],
            'is_credit' => 1,
            'qty' => $data['quantity'],
            'medium_info_id' => $medium_info_id,
            'created_at' => now()
        ]);

        return [
            'debit' => $debit_posting,
            'credit' => $credit_posting,
        ];
    }
}
