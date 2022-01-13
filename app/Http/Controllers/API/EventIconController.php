<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\EventIcon;

class EventIconController extends Controller
{

    public function index(Request $request )
    {
        // auth()->user()->id;
        $auth_id = 1;
        $event_icons = EventIcon::where("user_id", $auth_id)->orWhere("user_id", null )->get();
        if ( $event_icons->isNotEmpty() )
        {
            return response( $event_icons );
        }

        return response( [] );
    }

    public function store(Request $request )
    {
        $files = $request->file('icon');

            foreach ($files as $file) {
                $name = $file->getClientOriginalName();
                $file->move(public_path() . '/uploads/icons/', $name);

                $icon = new EventIcon([
                    "user_id" => 1,
                    "path" => '/uploads/icons/'.$file->getClientOriginalName()
                ]);
                $icon->save(); // Finally, save the record.


            }

        return response([ 'event' => $request ]);
    }
}
