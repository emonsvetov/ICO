<?php
  
namespace Database\Seeders;
  
use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $user = User::create(['organization_id' => 1, 'role_id' => 1, 'email' => 'admin@incentco.com', 'first_name' => 'Demo', 'last_name' => 'Demo', 'password' => '12345678', 'email_verified_at'=>'2022-02-17 23:30:36']);
        $role = Role::find(1);
        $user->assignRole($role);
    }
}