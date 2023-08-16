<?php
namespace App\Services\Program\Deposit;

use App\Services\Program\Deposit\CreditcardDepositService;
use App\Models\Program;

class DepositService
{
    public function deposit(Program $program, $data)   {
        if( $data['payment_kind'] && $data['payment_kind'] == 'creditcard') {
            if( $data['request_type'] == 'init' ) {
                return (new CreditcardDepositService)->init( $program, $data );
            }   else if ( $data['request_type'] == 'settlement' )  {
                if( isset($data['hash']) )   {
                    $decryptedHash = \Illuminate\Support\Facades\Crypt::decryptString($data['hash']);
                    $invoiceData = json_decode($decryptedHash);
                    if(json_last_error() !== JSON_ERROR_NONE)   {
                        throw new \InvalidArgumentException ( "Invalid hash value.", 400 );
                    }
                    return (new CreditcardDepositService)->finalize( $program, $invoiceData );
                }
            }
        }
    }
}
