<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Requests\EventIconRequest;
use App\Models\EventIcon;
use Illuminate\Support\Facades\Validator;

class EventIconController extends Controller
{

    public function index(Request $request )
    {
        $auth_id = 1;
        // $auth_id = auth()->user()->id;
        $event_icons = EventIcon::where("user_id", $auth_id)->orWhere("user_id", null )->get();
        if ( $event_icons->isNotEmpty() )
        {
            return response( $event_icons );
        }

        return response( [] );
    }

    public function store(Request $request )
    {
        // $auth_id = auth()->user()->id;
        $auth_id = 1;

        $validator = Validator::make($request->all(),
        [
            'icon' => 'required',
            'icon.*' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048'
        ]
        );
        if($validator->fails()) {
            return response()->json(["status" => "failed", "message" => "Validation error", "errors" => $validator->errors()]);
        }
        if($request->has('icon')) {
            foreach($request->file('icon') as $image) {
                $filename = time().rand(3). '.'.$image->getClientOriginalExtension();
                $image->move(public_path() . '/uploads/icons/', $filename);

                EventIcon::create([
                    "user_id" => $auth_id,
                    "path" => '/uploads/icons/'.$filename
                ]);
            }


            $response["status"] = "successs";
            $response["message"] = "Success! image(s) uploaded";
        }

        else {
            $response["status"] = "failed";
            $response["message"] = "Failed! image(s) not uploaded";
        }
        return response()->json($response);

        // $icon = new EventIcon([
        //     "user_id" => $auth_id,
        //     "path" => '/uploads/icons/'.$file->getClientOriginalName()
        // ]);

    }
}
