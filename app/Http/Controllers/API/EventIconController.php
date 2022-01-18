<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EventIcon;
use App\Models\Organization;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

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

    public function store(Request $request, Organization $organization  )
    {
        // $auth_id = auth()->user()->id;
        $auth_id = 1;

        $validator = Validator::make($request->all(),
        [
            'icon' => 'required',
            'icon.*' => 'required|image|mimes:jpeg,png,jpg,gif,ico|max:2048'
        ]
        );
        if($validator->fails()) {
            return response()->json(["status" => "failed", "message" => "Validation error", "errors" => $validator->errors()]);
        }
        if($request->has('icon')) {
            foreach($request->file('icon') as $image) {
                // $filename = time().rand(3, 5). '.'.$image->getClientOriginalExtension();

                $filename = $image->getClientOriginalName();
                $image->move(Storage::path('public/uploads/icons') , $filename);
                $path = 'uploads/icons/' . $filename ;
                EventIcon::create([
                    "user_id" => $auth_id,
                    "path" => $path,
                    "organization_id" => $organization->id
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

    }
}
