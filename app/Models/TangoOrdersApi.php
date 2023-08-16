<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TangoOrdersApi extends Model
{
    use HasFactory;
    protected $table = 'tango_orders_api';
    protected $guarded = [];

    public static function tango_orders_api_get_test() {
        return self::where('is_test', 1)->first();
    }

    public static function getActiveConfigurations() {

        $where = ['status' => 1];
        if(env('APP_ENV') != 'production'){
		    $where['is_test'] = 1;
        }else{
            $where['is_test'] = 0;
        }
        return self::where( $where )->get();
    }
}
