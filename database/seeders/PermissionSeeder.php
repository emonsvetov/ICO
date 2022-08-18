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
            'award-create',
            'checkout',
            'domain-list',
            'domain-view',
            'domain-create',
            'domain-edit',
            'domain-delete',
            'domain-generate-secretkey',
            'domain-add-ip',
            'domain-delete-ip',
            'domain-add-program',
            'domain-delete-program',
            'domain-listAvailableProgramsToAdd',
            'emailtemplate-list',
            'event-list',
            'event-view',
            'event-create',
            'event-edit',
            'event-delete',
            'eventtype-list',
            'merchant-list',
            'merchant-view',
            'merchant-create',
            'merchant-edit',
            'merchant-delete',
            'merchant-optimalvalue-add',
            'merchant-optimalvalue-edit',
            'merchant-optimalvalue-delete',
            'merchant-optimalvalue-list',
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
            'program-leaderboard-list',
            'program-leaderboard-view',
            'program-leaderboard-create',
            'program-leaderboard-update',
            'program-leaderboard-delete',
            'program-leaderboardType-list',
            'program-merchant-list',
            'program-merchant-add',
            'program-merchant-remove',
            'program-merchant-view',
            'program-merchant-view-giftcodes',
            'program-merchant-view-redeemable',
            'program-participant-list',
            'program-participant-change-status',
            'program-user-list',
            'program-user-add',
            'program-user-view',
            'program-user-update',
            'program-user-remove',
            'program-user-readbalance',
            'role-list',
            'role-view',
            'role-create',
            'role-edit',
            'role-delete',
            'reports-list',
            'reports-view',
            'submerchant-list',
            'submerchant-add',
            'submerchant-remove',
            'subprogram-list',
            'subprogram-unlink',
            'user-list',
            'user-view',
            'user-create',
            'user-edit',
            'user-delete',
            'user-program-list',
            'user-program-add',
            'user-program-remove',
            'user-program-roles',
			'can-invite',
			'can-invite-resend',
            'program-event-list',
            'program-event-view',
            'program-event-create',
            'program-event-update',
            'program-event-delete',
            'leaderboard-event-list',
            'leaderboard-event-assign',
            'program-social-wall-post-list',
            'program-social-wall-post-view',
            'program-social-wall-post-create',
            'program-social-wall-post-update',
            'program-social-wall-post-delete',
            'goal-plan-create',
            'goal-plan-list',
            'goal-plan-view',
            'goal-plan-update',
            'goal-plan-delete',
            'goal-plan-type-list',
            'expiration-rule-list'
        ];

        foreach ($permissions as $permission) {
             Permission::create(['name' => $permission, 'organization_id' => 1]);
        }
    }
}
