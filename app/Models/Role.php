<?php
namespace App\Models;
use Spatie\Permission\Models\Role as SpatieRole;
// use App\Models\Traits\WithOrganizationScope;

class Role extends SpatieRole
{
    // use WithOrganizationScope;

    protected $withPivot = [
        'program_id',
        'organization_id'
    ];

    const ROLE_SUPER_ADMIN = 'Super Admin';
    const ROLE_ADMIN = 'Admin';
    const ROLE_MANAGER = 'Manager';
    const ROLE_LIMITED_MANAGER = 'Limited Manager';
    const ROLE_READ_ONLY_MANAGER = 'Read Only Manager';
    const ROLE_PARTICIPANT = 'Participant';

    public static function getIdByNameAndOrg( $name, $organization_id, $insert = false ) {

        $id = self::where(['name' => $name, 'organization_id' => $organization_id])->first()->id;
        if( !$id && $insert)    {
            $id = self::insertGetId([
                'name'=>$name
            ]);
        }
        return $id;
    }
    public static function getIdByName( $name, $insert = false ) {
        $first = self::where('name', $name)->first();
        if( $first) return $first->id;
    }
    public static function getParticipantRoleId () {
        return self::getIdByName(self::ROLE_PARTICIPANT);
    }
    public static function getManagerRoleId () {
        return self::getIdByName(self::ROLE_MANAGER);
    }
}
