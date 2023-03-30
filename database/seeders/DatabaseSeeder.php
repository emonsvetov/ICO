<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\PermissionSeeder;
use Database\Seeders\RoleSeeder;
use Database\Seeders\EventTypeSeeder;
use Database\Seeders\JournalEventTypeSeeder;
use Database\Seeders\FinanceTypeSeeder;
use Database\Seeders\MediumTypeSeeder;
use Database\Seeders\AccountTypeSeeder;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\StatusSeeder;
use Database\Seeders\UserSeeder;
use Database\Seeders\OrganizationSeeder;
use Database\Seeders\OwnerSeeder;
use Database\Seeders\CallbackTypeSeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\StateSeeder;
use Database\Seeders\LeaderboardTypeSeeder;
use Database\Seeders\InvoiceTypeSeeder;
use Database\Seeders\PaymentMethodSeeder;
use Database\Seeders\EmailTemplateTypeSeeder;
use Database\Seeders\ExpirationRuleSeeder;
use Database\Seeders\GoalPlanTypeSeeder;
use Database\Seeders\MediaTypesSeeder;

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
            OwnerSeeder::class,
            OrganizationSeeder::class,
            PermissionSeeder::class,
            RoleSeeder::class,
            EventTypeSeeder::class,
            JournalEventTypeSeeder::class,
            FinanceTypeSeeder::class,
            MediumTypeSeeder::class,
            AccountTypeSeeder::class,
            CurrencySeeder::class,
            StatusSeeder::class,
            UserSeeder::class,
            CallbackTypeSeeder::class,
            CountrySeeder::class,
            StateSeeder::class,
            LeaderboardTypeSeeder::class,
            CsvImportTypeSeeder::class,
            InvoiceTypeSeeder::class,
            PaymentMethodSeeder::class,
            EmailTemplateTypeSeeder::class,
            ExpirationRuleSeeder::class,
            GoalPlanTypeSeeder::class,
            MediaTypesSeeder::class,
        ]);
    }
}
