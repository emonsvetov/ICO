<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        // \App\Models\User::factory(10)->create();
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
                'name' => 'create-permission-role',
                'description' => null,
                'created_at' => $current_datetime,
                'updated_at' => $current_datetime
            ],
            [
                'name' => 'update-permission-role',
                'description' => null,
                'created_at' => $current_datetime,
                'updated_at' => $current_datetime
            ],
            [
                'name' => 'delete-permission-role',
                'description' => null,
                'created_at' => $current_datetime,
                'updated_at' => $current_datetime
            ],
            [
                'name' => 'view-permission-role',
                'description' => null,
                'created_at' => $current_datetime,
                'updated_at' => $current_datetime
            ],
        ]);
    }
}
