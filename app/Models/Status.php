<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    use HasFactory;
    protected $guarded = [];
    protected $hidden = ['created_at', 'updated_at', 'context'];

    public static function getSetByContextAndName( $context, $name, $insert = true ) {
        if( !$context || !$name) return;
        $id = self::where(['status' => $name, 'context' => $context])->first()->id;
        if( !$id && $insert)    {
            $id = self::insertGetId([
                'status'=>$name,
                'context'=>$context
            ]);
        }
        return $id;
    }

    public static function getByNameAndContext( $name, $context) {
        if( !$context || !$name) return;
        return self::where(['status' => $name, 'context' => $context])->first();
    }

    public static function get_order_pending_status()  {
        return self::getSetByContextAndName('Orders', 'Pending');
    }
    
    public static function get_order_shipped_state()  {
        return self::getSetByContextAndName('Orders', 'Shipped');
    }

    public static function get_goal_active_state()  {
        return self::getSetByContextAndName('Goals', 'Active');
    }
}
