<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\EventTypeSeeder;
use Database\Seeders\JournalEventTypeSeeder;
use Database\Seeders\FinanceTypeSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call([
            EventTypeSeeder::class,
            JournalEventTypeSeeder::class,
            FinanceTypeSeeder::class,
            PermissionSeeder::class,
            RoleSeeder::class
        ]);
    }
}
