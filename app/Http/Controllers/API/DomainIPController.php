<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\DomainIPRequest;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\DomainIP;
use App\Models\Domain;

class DomainIPController extends Controller
{
    public function store(DomainIPRequest $request, Organization $organization, Domain $domain )
    {
        if ( !$organization OR !$domain )
        {
            return response(['errors' => 'Invalid Organization or Domain'], 422);
        }

        $newDomainIP = DomainIP::create(
            $request->validated() + 
            [
                'domain_id' => $domain->id
            ]
        );

        if ( !$newDomainIP )
        {
            return response(['errors' => 'Doman IP creation failed'], 422);
        }

        return response([ 'domain_ip' => $newDomainIP ]);
    }

    public function delete(Organization $organization, Domain $domain, DomainIP $domain_ip )
    {
        if ( !$organization || !$domain )
        {
            return response(['errors' => 'Invalid Organization or Domain'], 422);
        }
        $deleted = $domain_ip->delete();
        return response()->json( $deleted );
    }
}
