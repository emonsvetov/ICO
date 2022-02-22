<?php
  
namespace Database\Seeders;
  
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
  
class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        $permissions = [
           'organization-list',
           'organization-create',
           'organization-edit',
           'organization-delete',
           'user-list',
           'user-create',
           'user-edit',
           'user-delete',
           'role-list',
           'role-create',
           'role-edit',
           'role-delete',
           'permission-list',
           'permission-create',
           'permission-edit',
           'permission-delete',
           'domain-list',
           'domain-create',
           'domain-edit',
           'domain-delete',
           'program-list',
           'program-create',
           'program-edit',
           'program-delete',
           'program-merchant-list',
           'program-merchant-edit',
           'program-user-list',
           'program-user-edit',
           'merchant-list',
           'merchant-create',
           'merchant-edit',
           'merchant-delete',
           'reports-list',
           'reports-view',
           'physical-order-list',
           'physical-order-view',
        ];
     
        foreach ($permissions as $permission) {
             Permission::create(['name' => $permission, 'guard_name' => 'api']);
        }
    }
}