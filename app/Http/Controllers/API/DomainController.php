<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Requests\DomainRequest;
use App\Models\Organization;
use Illuminate\Support\Str;
use App\Models\Domain;
Use Exception;

class DomainController extends Controller
{
    public function index( Organization $organization )
    {
        $status = request()->get('status');
        $keyword = request()->get( 'keyword' );
        $sortby = request()->get('sortby', 'id');
        $direction = request()->get('direction', 'asc');

        $where = [];

        if( $status )
        {
            $where[] = ['status', $status];
        }
        
        if( $sortby == 'name' )
        {
            $collation =  "COLLATE utf8mb4_unicode_ci"; //COLLATION is required to support case insensitive ordering
            $orderByRaw = "{$sortby} {$collation} {$direction}";
        }
        else
        {
            $orderByRaw = "{$sortby} {$direction}";
        }

        $query = Domain::where($where);

        if( $keyword )
        {
            $query = $query->where(function($query1) use($keyword) {
                $query1->orWhere('id', 'LIKE', "%{$keyword}%")
                ->orWhere('name', 'LIKE', "%{$keyword}%");
            });
        }

        $query = $query->orderByRaw($orderByRaw);
        
        if ( request()->has('minimal') )
        {
            $domains = $query->select('id', 'name')->withOrganization($organization)->get();
        }
        else 
        {
            $domains = $query->withOrganization($organization)->paginate(request()->get('limit', 10));
        }

        if ( $domains->isNotEmpty() ) 
        { 
            return response( $domains );
        }

        return response( [] );
    }

    public function store(DomainRequest $request, Organization $organization )
    {
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
        $domain->domain_ips; //trigger association

        if ( $domain ) 
        {
            return response( $domain );
        }

        return response( [] );
    }

    public function update(DomainRequest $request, Organization $organization, Domain $domain )
    {
        $domain->update( $request->validated() );
        return response([ 'domain' => $domain ]);
    }

    public function delete(Organization $organization, Domain $domain )
    {
        $deleted = ['deleted' => 1];
        $domain->update( $deleted );
        return response()->json( $deleted );
    }

    public function generateSecretKey(Organization $organization, Domain $domain )
    {
        $secret_key = sha1 ( Str::random(10) );
        return response([ 'secret_key' => $secret_key ]);
    }
}
