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
use Database\Seeders\MediumTypeSeeder;
use Database\Seeders\AccountTypeSeeder;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\StatusSeeder;

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
            PermissionSeeder::class,
            RoleSeeder::class,
            EventTypeSeeder::class,
            JournalEventTypeSeeder::class,
            FinanceTypeSeeder::class,
            MediumTypeSeeder::class,
            AccountTypeSeeder::class,
            CurrencySeeder::class,
            StatusSeeder::class,
        ]);
    }
}
