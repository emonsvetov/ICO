<?php

namespace App\Models;

use App\Models\BaseModel;

class InvoiceType extends BaseModel
{
    protected $guarded = [];
    public $timestamps = true;
    
    const INVOICE_TYPE_ON_DEMAND = 'On-Demand';
    const INVOICE_TYPE_MONTHLY = 'Monthly';
    const INVOICE_TYPE_CREDITCARD = 'Credit Card Deposit';

    public function getIdByTypeOnDemand( $insert = false)   {
        return self::getIdByName(self::INVOICE_TYPE_ON_DEMAND, $insert);
    }    
    public function getIdByTypeMonthly( $insert = false)   {
        return self::getIdByName(self::INVOICE_TYPE_MONTHLY, $insert);
    }
    public function getIdByTypeCreditCard( $insert = false)   {
        return self::getIdByName(self::INVOICE_TYPE_CREDITCARD, $insert);
    }
}
