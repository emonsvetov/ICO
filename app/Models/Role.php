<?php
namespace App\Models;
use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected $withPivot = [
        'program_id'
    ];

    public function getIdByNameAndOrg( $name, $organization_id, $insert = false ) {

        $id = self::where(['name' => $name, 'organization_id' => $organization_id])->first()->id;
        if( !$id && $insert)    {
            $id = self::insertGetId([
                'name'=>$name
            ]);
        }
        return $id;
    }
}
