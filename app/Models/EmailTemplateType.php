<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EmailTemplateType extends Model
{
    protected $guarded = [];

    protected $hidden = [
        'created_at',
        'updated_at',
        'deleted',
    ];

    const FIELD_NAME = 'type';
    const EMAIL_TEMPLATE_TYPE_AWARD = 'Award';
    const EMAIL_TEMPLATE_TYPE_ALLOCATE_PEER_TO_PEER = 'Allocate Peer to Peer';
    const EMAIL_TEMPLATE_TYPE_AWARD_BADGE = 'Award Badge';

    public static function getIdByType( $type, $insert = false ) {
        $first = self::where(self::FIELD_NAME, $type)->first();
        if( $first) return $first->id;
        if( $insert )    {
            return self::insertGetId([
                self::FIELD_NAME => $type
            ]);
        }
    }
}
