<?php
namespace App\Http\Traits;
use DB;

trait IndexableTrait {

    public $model;
    public $organization;
    public $field_keyword = 'keyword';
    public $field_name = 'name';

    public function indexable( ) {
        $status = request()->get('status');
        $keyword = request()->get( $this->field_keyword );
        $sortby = request()->get('sortby', 'id');
        $direction = request()->get('direction', 'asc');

        // return $this->organization->id;
        $where = [];
        if( $this->organization )
        {
            $where = ['organization_id'=>$this->organization->id];
        }

        if( $status )
        {
            $where[] = ['status', $status];
        }
        
        if( $sortby == $this->field_name )
        {
            $collation =  "COLLATE utf8mb4_unicode_ci"; //COLLATION is required to support case insensitive ordering
            $orderByRaw = "{$sortby} {$collation} {$direction}";
        }
        else
        {
            $orderByRaw = "{$sortby} {$direction}";
        }

        $query = $this->model->where($where);

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
            $results = $query->select('id', 'name')
                            //   ->with(['children' => function($query){
                            //       return $query->select('id','name','program_id');
                            //   }])
                            ->get();
        }
        else 
        {
            $results = $query->paginate(request()->get('limit', 10));
        }

        return $results;
    }
}