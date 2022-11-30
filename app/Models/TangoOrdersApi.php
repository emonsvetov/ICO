<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\TangoOrder;

class TangoOrdersApi extends Model
{
    use HasFactory;
    protected $table = 'tango_orders_api';
    protected $guarded = [];
    public static function tango_orders_api_get_test() {
        return self::where('is_test', 1)->first();
    }
}
