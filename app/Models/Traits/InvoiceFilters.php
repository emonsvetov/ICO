<?php
namespace App\Models\Traits;

trait InvoiceFilters
{
    public function applyFilters()    {
        $key = request()->get('key', '');
        if( $key ) {
            self::$query = self::$query->where(function($query1) use($key) {
                $query1->where('key', 'LIKE', "%{$key}%");
            });
        }
    }
}