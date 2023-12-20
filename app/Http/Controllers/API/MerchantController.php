<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\DB;

use App\Http\Traits\MerchantMediaUploadTrait;
use App\Http\Requests\MerchantStatusRequest;
use App\Http\Requests\MerchantRequest;
use App\Http\Controllers\Controller;
use App\Http\Requests\ToaRequest;
use App\Models\TangoOrdersApi;
use Illuminate\Http\Request;
use App\Models\Organization;
use App\Models\Merchant;

class MerchantController extends Controller
{
    use MerchantMediaUploadTrait;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Organization $organization)
    {

        die("Jere");

        $keyword = request()->get('keyword');
        $sortby = request()->get('sortby', 'id');
        $direction = request()->get('direction', 'asc');
        $tree = request()->has('tree') ? true : false;

        $where = [];

        if( $sortby == "name" )
        {
            $collation =  "COLLATE utf8mb4_unicode_ci"; //COLLATION is required to support case insensitive ordering
            $orderByRaw = "{$sortby} {$collation} {$direction}";
        }
        else
        {
            $orderByRaw = "{$sortby} {$direction}";
        }

        $query = Merchant::where( $where );

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
            $query->select('id', 'name')
            ->with(['children' => function($query){
                return $query->select('id','name','parent_id')
                ->with(['children' => function($query){
                    return $query->select('id','name','parent_id');
                }]);
            }]);
            if ($tree){
                $query->whereNull('parent_id');
            }
            $merchants = $query->get();
        } else {
            $query->with('children');
            if ($tree){
                $query->whereNull('parent_id');
            }
            $merchants = $query->paginate(request()->get('limit', 50));
        }

        if ( $merchants->isNotEmpty() )
        {
            return response( $merchants );
        }

        return response( [] );
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(MerchantRequest $request)
    {
        try {
            $exists = Merchant::where('name', $request->get('name'))->first();
            if ($exists){
                return response([ 'merchant' => $exists ]);
            }
            $newMerchant = (new \App\Models\Merchant)->createAccount( $request->validated() );
        } catch (\Exception $exception){
            return response(['errors' => 'Merchant Creation failed. ' . $exception->getMessage()], 422);
        }

        if ( !$newMerchant )
        {
            return response(['errors' => 'Merchant Creation failed'], 422);
        }

        $uploads = $this->handleMerchantMediaUpload( $request, $newMerchant );
        if( $uploads )   {
            $newMerchant->update( $uploads );
        }
        return response([ 'merchant' => $newMerchant ]);
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\Models\Merchant  $merchant
     * @return \Illuminate\Http\Response
     */
    public function show( Merchant $merchant )
    {
        if ( ! $merchant->exists )
        {
            return response(['errors' => 'Merchant Not Found'], 404);
        }
        // DB::enableQueryLog();
        $merchant->inventoryCount();
        $merchant->redeemedCount();
        $merchant->children;
        $merchant->tangoOrdersApi;

        return response( $merchant );
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Merchant  $merchant
     * @return \Illuminate\Http\Response
     */
    public function update(MerchantRequest $request, Merchant $merchant)
    {
        if ( ! $merchant->exists )
        {
            return response(['errors' => 'No Merchant Found'], 404);
        }

        $oldMerchant = $merchant->toArray(); //need to fetch it before updating merchant as update or validate call patches $merchant field with file object in from request!

        $merchant->update( $request->validated() );

        $uploads = $this->handleMerchantMediaUpload( $request, $oldMerchant, true );
        if( $uploads )   {
            $merchant->update( $uploads );
        }

        return response([ 'merchant' => $merchant ]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\Models\Merchant  $merchant
     * @return \Illuminate\Http\Response
     */
    public function delete(Merchant $merchant)
    {
        $merchant->delete();
        return response( ['deleted' => true] );
    }

    public function changeStatus(MerchantStatusRequest $request, Merchant $merchant)
    {

        if ( ! $merchant->exists )
        {
            return response(['errors' => 'No Merchant Found'], 404);
        }

        $merchant->update( $request->validated() );

        return response( ['updated' => true] );
    }

    public function updateToa(ToaRequest $request, Merchant $merchant, TangoOrdersApi $toa)
    {

        return response( $toa->update($request->validated()) );
    }
}
