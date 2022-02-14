<?php

namespace App\Http\Controllers\API;

use App\Http\Traits\MerchantMediaUploadTrait;
use App\Http\Requests\MerchantStatusRequest;
use App\Http\Requests\MerchantRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Merchant;
use App\Models\MerchantNode;
use App\Models\Node;

class MerchantController extends Controller
{
    use MerchantMediaUploadTrait;
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        
        $keyword = request()->get('keyword');
        $sortby = request()->get('sortby', 'id');
        $direction = request()->get('direction', 'asc');

        $where = ['deleted'=>0];

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
            $merchants = $query->select('id', 'name')->get();
        } else {
            $merchants = $query->paginate(request()->get('limit', 10));
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
        $newMerchant = Merchant::create( $request->validated() );

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

        // Node::find(4)->moveTo(5, Node::find(1)); // same as Node::find(4)->moveTo(0, 2);
        // Node::find(3)->moveTo(3, Node::find(2)); // same as Node::find(4)->moveTo(0, 2);

        // return Node::find(3)->getChildren()->pluck('id')->toArray(); // [4]
$parent = Node::find(1);
$node = Node::find(2);
$parent->addChild($node);
$node = Node::find(3);

        // $parent->addChild($node);
        
$children = Node::find(2)->getChildren();
return $tree = $children->toTree();
Node::createFromArray([
    [
        'id' => 1,
        'children' => [
            [
                'id' => 2,
                'childern' => [
                    ['id' => 3],
                    ['id' => 4],
                    ['id' => 5],
                ]
            ],
            ['id' => 3],
        ]
    ]
]);
        // $nodes = [
        //     new Node(['id' => 1]),
        //     new Node(['id' => 2]),
        //     new Node(['id' => 3]),
        //     new Node(['id' => 4, 'parent_id' => 1])
        // ];
        
        // foreach ($nodes as $node) {
        //     $node->save();
        // }
        exit;
        
        return MerchantNode::getRoots()->toArray(); // [1, 2, 3]
        MerchantNode::find(1)->isRoot(); // true
        MerchantNode::find(1)->isParent(); // true
        MerchantNode::find(4)->isRoot(); // false
        MerchantNode::find(4)->isParent(); // false
        exit;
        
        if ( $merchant ) 
        { 
            $merchant->submerchants;
            return response( $merchant );
        }

        return response( [] );
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

        $merchant->update(['deleted'=>true]);

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
}
