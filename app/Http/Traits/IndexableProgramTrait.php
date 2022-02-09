<?php
namespace App\Http\Traits;

use App\Models\Organization;

trait IndexableProgramTrait {
    private $filterByOrganization = false;
    public function setFilterByOrganization ( $flag )  {
        $this->filterByOrganization = $flag;
    }
    public function indexable( Organization $organization, $Model ) {
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

        $query = $Model::where($where);

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

        return $domain;
    }
}