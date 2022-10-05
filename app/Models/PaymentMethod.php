<?php

namespace App\Models;

use App\Models\BaseModel;

class PaymentMethod extends BaseModel
{
    protected $guarded = [];
    public $timestamps = true;
    const PAYMENT_METHOD_ACH = 'ACH';
    const PAYMENT_METHOD_CHECK = 'Check';
    const PAYMENT_METHOD_WIRE_TRANSFER = 'Wire Transfer';
    const PAYMENT_METHOD_CREDITCARD = 'Credit Card';

    public static function getPaymentMethodAch() {
        return self::getIdByName(self::PAYMENT_METHOD_ACH);
    }
    public static function getPaymentMethodCheck( $insert = false ) {
        // return self::PAYMENT_METHOD_CHECK;
        return self::getIdByName(self::PAYMENT_METHOD_CHECK, $insert);
    }
    public static function getPaymentMethodWireTransfer() {
        return self::getIdByName(self::PAYMENT_METHOD_WIRE_TRANSFER);
    }
    public static function getPaymentMethodCreditcard() {
        return self::getIdByName(self::PAYMENT_METHOD_CREDITCARD);
    }
}
