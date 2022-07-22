<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Status extends Model
{
    use HasFactory;
    protected $guarded = [];

    public function getSetByContextAndName( $context, $name, $insert = true ) {
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

    public function getByNameAndContext( $name, $context) {
        if( !$context || !$name) return;
        return self::where(['status' => $name, 'context' => $context])->first();
    }

    public function get_order_pending_status()  {
        return self::getSetByContextAndName('Orders', 'Pending');
    }
}
