<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Http\Requests\DomainRequest;
use App\Models\Organization;
use App\Models\Domain;

class DomainController extends Controller
{
    public function index( Organization $organization )
    {
        if ( $organization )
        {
            // $status = request()->get('status');
            $keyword = request()->get('keyword');
            $sortby = request()->get('sortby', 'id');
            $direction = request()->get('direction', 'asc');

            $where = ['organization_id'=>$organization->id, 'deleted'=>0];

            if( $sortby == "name" )
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
                $domains = $query->select('id', 'name')
                                //   ->with(['children' => function($query){
                                //       return $query->select('id','name','program_id');
                                //   }])
                                ->get();
            }
            else 
            {
                $domains = $query->paginate(request()->get('limit', 10));
            }
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

        $secret_key = sha1 ( generateRandomString () );

        return response([ 'secret_key' => $secret_key ]);
    }
}
