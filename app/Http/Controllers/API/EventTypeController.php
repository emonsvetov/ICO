<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\EventType;

class EventTypeController extends Controller
{
    public function index()
    {
        $eventTypes = EventType::get();
        if ( $eventTypes->isNotEmpty() )
        {
            return response( $eventTypes );
        }
        return response( [] );
    }
}
