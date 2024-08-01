<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EventIcon;
use App\Models\Organization;
use App\Http\Requests\EventIconRequest;
use Illuminate\Support\Facades\Storage;

class EventIconController extends Controller
{

    public function index( Request $request, Organization $organization )
    {
        if ( !$organization )
        {
            return response(['errors' => 'Invalid Organization'], 422);
        }

        $include = $request->get('include', 'program');

        $query = EventIcon::query();
        $query->where("deleted",  0);
        if( $include == 'both' ) {
            $query->orWhere(function($query) use($organization) {
                $query->where('organization_id', 0);
                $query->where('organization_id', $organization->id);
            });
        }   else if( $include == 'global' )   {
            $query->where('organization_id', 0);
        }   else if( $include == 'program' )   {
            $query->where('organization_id', $organization->id);
        }

        $eventIcons = $query->get();
        $filesystemDriver = config('filesystems.default');

        $eventIcons = $eventIcons->filter(function($item) use ($filesystemDriver) {
            return Storage::disk($filesystemDriver)->exists($item->path);
        });

        if ( $eventIcons->isNotEmpty() )
        {
            return response( $eventIcons->values() );
        }
        return response( [] );
    }

    public function store(EventIconRequest $request, Organization $organization  )
    {
        if ( !$organization )
        {
            return response(['errors' => 'Invalid Organization'], 422);
        }

        try {
            $icons = [];
            if( $request->get('icon_upload_type') && $request->get('icon_upload_type') === 'global') {
                $organizationId = 0;
            }   else {
                $organizationId = $organization->id;
            }
            if($request->has('image')) {
                foreach($request->file('image') as $icon) {
                    $path = $icon->store('eventIcons');
                    $icons[] = $created = EventIcon::create([
                        "name" => $icon->getClientOriginalName(),
                        "path" => $path,
                        "organization_id" => $organizationId
                    ]);
                }
            }
        } catch (\Exception $e )    {
            return response(['errors' => $e->getMessage()], 422);
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
