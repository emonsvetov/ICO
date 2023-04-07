<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Events\OrganizationCreated;

use App\Http\Requests\OrganizationRequest;
use App\Models\Organization;
use App\Models\User;

class OrganizationController extends Controller
{
    public function index()
    {
        $organization = Organization::orderBy('name')->get();

        if ( $organization->isNotEmpty() )
        {
            return response( $organization );
        }

        return response( [] );
    }

    public function store(OrganizationRequest $request)
    {

        if($request->get('name')){
            $exists = Organization::where('name', $request->get('name'))->first();
            if ($exists){
                return response([ 'organization' => $exists ]);
            }
        }

        $organization = Organization::create( $request->validated() );

        if ( !$organization )
        {
            return response(['errors' => 'Organization Creation failed'], 422);
        }

        OrganizationCreated::dispatch($organization);

        return response([ 'organization' => $organization ]);
    }

    public function show(Organization $organization)
    {
        if ( $organization )
        {
            return response( $organization );
        }

        return response( [] );
    }

    public function update(OrganizationRequest $request, Organization $organization)
    {
        if ( ! $organization->exists )
        {
            return response(['errors' => 'No Organization Found'], 404);
        }

        $organization->update( $request->validated() );

        return response([ 'organization' => $organization ]);
    }
}
