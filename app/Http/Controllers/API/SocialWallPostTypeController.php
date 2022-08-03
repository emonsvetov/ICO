<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\SocialWallPostType;

class SocialWallPostTypeController extends Controller
{

    public function event()
    {
        return SocialWallPostType::getEventType();
    }

    public function message()
    {
        return SocialWallPostType::getMessageType();
    }

    public function comment()
    {
        return SocialWallPostType::getCommentType();
    }
}
