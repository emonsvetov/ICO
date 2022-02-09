<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\DomainAddProgramRequest;
use App\Http\Traits\IndexableDomainTrait;
use App\Http\Requests\DomainIPRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\DomainRequest;
use App\Models\DomainProgram;
use App\Models\Organization;
use Illuminate\Support\Str;
use App\Models\DomainIP;
use App\Models\Domain;
Use Exception;

class DomainController extends Controller
{
    use IndexableDomainTrait;

    public function index( Organization $organization, Domain $domain )
    {
        if ( $organization )
        {
            $this->model = $domain;
            $this->organization = $organization;
            $domains = $this->indexable_domain();
        }
        else
        {
            return response(['errors' => 'Invalid Organization'], 422);
        }

        if ( $domains->isNotEmpty() ) 
        { 
            return response( $domains );
        }

        return response( [] );
    }

    public function store(DomainRequest $request, Organization $organization )
    {
                 
        if ( !( $organization->id ) )
        {
            return response(['errors' => 'Invalid Organization'], 422);
        }
        
        $newDomain = Domain::create( 
            $request->validated() + 
            [
                'organization_id' => $organization->id
            ]
        );

        if ( !$newDomain )
        {
            return response(['errors' => 'Doman creation failed'], 422);
        }

        return response([ 'domain' => $newDomain ]);
    }

    public function show( Organization $organization, Domain $domain )
    {
        if ( !$organization->id )        
        {
            return response(['errors' => 'Invalid Organization'], 422);
        }

        $domain->domain_ips; //trigger association

        if ( $domain ) 
        {
            return response( $domain );
        }

        return response( [] );
    }

    public function update(DomainRequest $request, Organization $organization, Domain $domain )
    {
        if ( !$organization->id || !$domain->id )
        {
            return response(['errors' => 'Invalid Organization or Domain'], 422);
        }
        
        if ( $domain->organization_id != $organization->id ) 
        { 
            return response(['errors' => 'Invalid Organization or Domain'], 404);
        }

        $domain->update( $request->validated() );

        return response([ 'domain' => $domain ]);
    }

    public function delete(Organization $organization, Domain $domain )
    {
        if ( !$organization || !$domain )
        {
            return response(['errors' => 'Invalid Organization or Domain'], 422);
        }
        $deleted = ['deleted' => 1];
        $domain->update( $deleted );
        return response()->json( $deleted );
    }

    public function generateSecretKey(Organization $organization, Domain $domain )
    {
        if ( !$organization OR !$domain )
        {
            return response(['errors' => 'Invalid Organization or Domain'], 422);
        }

        $secret_key = sha1 ( Str::random(10) );

        return response([ 'secret_key' => $secret_key ]);
    }
}
