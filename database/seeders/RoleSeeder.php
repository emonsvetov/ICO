<?php
  
namespace Database\Seeders;
  
use Illuminate\Database\Seeder;

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

        $role = Role::create(['name' => 'Super Admin']);
        $role = Role::create(['name' => 'Admin']);
        $role = Role::create(['name' => 'Manager']);
        $role = Role::create(['name' => 'Limited Manager']);
        $role = Role::create(['name' => 'Read Only Manager']);
        $role = Role::create(['name' => 'Participant']);
        // $permissions = Permission::pluck('id','id')->all();
        // $role->syncPermissions($permissions);
        // $user = User::find(1); //ID:1 should always be a super admin!
        // $user->assignRole($role);
    }
}