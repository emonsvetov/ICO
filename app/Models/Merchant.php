<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;

class Merchant extends Model
{
    use HasFactory;
    use SoftDeletes;
  
    protected $guarded = [];

    public function findByIds($ids = [])
    {
         //not sure whether to get with tree, if so, uncomment next line and the query block with "children"
        // $query = $this->where('parent_id', null);
        $query = $this;
        if( is_array( $ids ) && count( $ids ) > 0 ) 
        {
            $query = $query->whereIn( 'id',  $ids);
        }
        // return $query->select('id','name','parent_id')
        // ->with(['children' => function($q1) {
        //     return $q1->select('id','name','parent_id')
        //     ->with(['children' => function($q2){
        //         return $q2->select('id','name','parent_id');
        //     }]);
        // }])->get();
        return $query->get();
    }

    public function children()
    {
        return $this->hasMany(Merchant::class, 'parent_id')->with('children');
    }

    public function optimal_values()
    {
        return $this->hasMany(OptimalValue::class);
    }

    public function programs()
    {
        return $this->belongsToMany(Program::class, 'program_merchant');
    }
}
