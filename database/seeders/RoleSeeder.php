<?php
  
namespace Database\Seeders;
  
use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $role = Role::create(['name' => 'Super Admin', 'organization_id' => 1]);
        $role = Role::create(['name' => 'Admin', 'organization_id' => 1]);
        $role = Role::create(['name' => 'Manager', 'organization_id' => 1]);
        $role = Role::create(['name' => 'Participant', 'organization_id' => 1]);
        // $permissions = Permission::pluck('id','id')->all();
        // $role->syncPermissions($permissions);
        // $user = User::find(1); //ID:1 should always be a super admin!
        // $user->assignRole($role);
    }
}