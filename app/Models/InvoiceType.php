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

    public static function getIdByName( $name, $insert = false, $description = '') : int   {
        $row = self::where('name', $name)->first();
        $id = $row->id ?? null;

        if( !$id && $insert)    {
            $id = self::insertGetId([
                'name'=>$name,
                'description'=>$description
            ]);
        }
        return $id;
    }    

    public static function getIdByTypeOnDemand( $insert = false)   {
        return self::getIdByName(self::INVOICE_TYPE_ON_DEMAND, $insert);
    }    
    public static function getIdByTypeMonthly( $insert = false)   {
        return self::getIdByName(self::INVOICE_TYPE_MONTHLY, $insert);
    }
    public static function getIdByTypeCreditCard( $insert = false)   {
        return self::getIdByName(self::INVOICE_TYPE_CREDITCARD, $insert);
    }
}
