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
            'domain-list',
            'domain-view',
            'domain-create',
            'domain-edit',
            'domain-delete',
            'event-list',
            'event-view',
            'event-create',
            'event-edit',
            'event-delete',
            'merchant-list',
            'merchant-view',
            'merchant-create',
            'merchant-edit',
            'merchant-delete',
            'organization-list',
            'organization-view',
            'organization-create',
            'organization-edit',
            'organization-delete',
            'permission-list',
            'permission-view',
            'permission-create',
            'permission-edit',
            'permission-delete',
            'physical-order-list',
            'physical-order-view',
            'program-list',
            'program-view',
            'program-create',
            'program-edit',
            'program-delete',
            'program-merchant-list',
            'program-merchant-view',
            'program-merchant-edit',
            'program-user-list',
            'program-user-edit',
            'role-list',
            'role-view',
            'role-create',
            'role-edit',
            'role-delete',
            'reports-list',
            'reports-view',
            'user-list',
            'user-view',
            'user-create',
            'user-edit',
            'user-delete',
            'user-program-list',
            'user-program-add',
            'user-program-remove',
        ];
     
        foreach ($permissions as $permission) {
             Permission::create(['name' => $permission, 'organization_id' => 1]);
        }
    }
}