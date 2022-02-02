<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PermissionSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $current_datetime = Carbon::now()->format('Y-m-d H:i:s');
        DB::table('permissions')->insert([
            [
                'name' => 'create-user',
                'description' => null,
                'created_at' => $current_datetime,
                'updated_at' => $current_datetime
            ],
            [
                'name' => 'update-user',
                'description' => null,
                'created_at' => $current_datetime,
                'updated_at' => $current_datetime
            ],
            [
                'name' => 'delete-user',
                'description' => null,
                'created_at' => $current_datetime,
                'updated_at' => $current_datetime
            ],
            [
                'name' => 'view-user',
                'description' => null,
                'created_at' => $current_datetime,
                'updated_at' => $current_datetime
            ],
            [
                'name' => 'create-organization',
                'description' => null,
                'created_at' => $current_datetime,
                'updated_at' => $current_datetime
            ],
            [
                'name' => 'update-organization',
                'description' => null,
                'created_at' => $current_datetime,
                'updated_at' => $current_datetime
            ],
            [
                'name' => 'delete-organization',
                'description' => null,
                'created_at' => $current_datetime,
                'updated_at' => $current_datetime
            ],
            [
                'name' => 'view-organization',
                'description' => null,
                'created_at' => $current_datetime,
                'updated_at' => $current_datetime
            ],
            [
                'name' => 'create-role-permission',
                'description' => null,
                'created_at' => $current_datetime,
                'updated_at' => $current_datetime
            ],
            [
                'name' => 'update-role-permission',
                'description' => null,
                'created_at' => $current_datetime,
                'updated_at' => $current_datetime
            ],
            [
                'name' => 'delete-role-permission',
                'description' => null,
                'created_at' => $current_datetime,
                'updated_at' => $current_datetime
            ],
            [
                'name' => 'view-role-permission',
                'description' => null,
                'created_at' => $current_datetime,
                'updated_at' => $current_datetime
            ],
            [
                'name' => 'create-domain',
                'description' => null,
                'created_at' => $current_datetime,
                'updated_at' => $current_datetime
            ],
            [
                'name' => 'update-domain',
                'description' => null,
                'created_at' => $current_datetime,
                'updated_at' => $current_datetime
            ],
            [
                'name' => 'delete-domain',
                'description' => null,
                'created_at' => $current_datetime,
                'updated_at' => $current_datetime
            ],
            [
                'name' => 'view-domain',
                'description' => null,
                'created_at' => $current_datetime,
                'updated_at' => $current_datetime
            ],
        ]);
    }
}
