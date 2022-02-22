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

        // $role = Role::create(['name' => 'Super Admin', 'guard_name' => 'api']);
        // $permissions = Permission::pluck('id','id')->all();
        // $role->syncPermissions($permissions);
        $user = User::find(1);
        $role = Role::find(1);
        $user->assignRole([$role->id]);
    }
}