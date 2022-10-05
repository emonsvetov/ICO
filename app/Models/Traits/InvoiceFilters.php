<?php
namespace App\Models\Traits;

trait InvoiceFilters
{
    public function applyFilters()    {
        $key = request()->get('key', '');
        $minimal = request()->get('minimal', '');
        if( $key ) {
            self::$query->where(function($query1) use($key) {
                $query1->where('key', 'LIKE', "%{$key}%");
            });
        }
        if( $minimal ) {
            self::$query->select('id', 'key', 'seq'); //important to generate "invoice_number"
        }   else {
            self::$query->with('invoice_type');
        }
    }
}