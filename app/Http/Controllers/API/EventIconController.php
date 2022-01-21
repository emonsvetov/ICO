<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EventIcon;
use App\Models\Organization;
use App\Http\Requests\EventIconRequest;

class EventIconController extends Controller
{

    public function index( Request $request, Organization $organization )
    {
        if ( !$organization )
        {
            return response(['errors' => 'Invalid Organization'], 422);
        }

        $eventIcons = EventIcon::where("deleted", 0)->get();
        if ( $eventIcons->isNotEmpty() )
        {
            return response( $eventIcons );
        }
        return response( [] );
    }

    public function store(EventIconRequest $request, Organization $organization  )
    {
        if ( !$organization )
        {
            return response(['errors' => 'Invalid Organization'], 422);
        }
        $icons = [];
        if($request->has('icon')) {
            foreach($request->file('icon') as $icon) {
                $path = $icon->store('eventIcons');
                $icons[] = $created = EventIcon::create([
                    "name" => $icon->getClientOriginalName(),
                    "path" => $path,
                    "organization_id" => $organization->id
                ]);
            }
        }
        return response()->json($icons);
    }

    public function delete(Organization $organization, EventIcon $eventIcon )
    {
        if ( !$organization )
        {
            return response(['errors' => 'Invalid Organization'], 422);
        }
        $deleted = ['deleted' => 1];
        $eventIcon->update( $deleted );
        return response()->json( $deleted );
    }
}
