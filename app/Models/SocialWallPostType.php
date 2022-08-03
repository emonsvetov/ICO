<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\BaseModel;

class SocialWallPostType extends BaseModel
{
    use HasFactory;

    const TYPE_EVENT = 'Event';
    const TYPE_MESSAGE = 'Message';
    const TYPE_COMMENT = 'Comment';

    protected $guarded = [];

    public static function getEventType()
    {
        return self::where('type', self::TYPE_EVENT)->first();
    }

    public static function getEventTypeId()
    {
        $type = self::where('type', self::TYPE_EVENT)->first();
        return $type->id ?? null;
    }

    public static function getMessageType()
    {
        return self::where('type', self::TYPE_MESSAGE)->first();
    }

    public static function getCommentType()
    {
        return self::where('type', self::TYPE_COMMENT)->first();
    }

}
