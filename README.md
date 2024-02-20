## About Incentco

[description here]

## Common Conventions

Use Singuler name for Controller name. For example `MerchantController.php`

## make Controller (for API), Model & Form Requests

`php artisan make:controller DomainController --api --model=Domain --requests`

## Create policy class for a given model

`php artisan make:policy MerchantPolicy --model=Merchant`

## v2 to v3 Migrations

# Migrate Programs
`php artisan v2migrate:programs`
    - Creates Organizations, Programs & Subprograms, Domains (Create, Assign Domains, Domain Ips etc), Addresses, Events, Leaderboards, LeaderboardEvents, Invoices, Accounts, JounralEvents & Postings.

# Migrate Merchants
`php artisan v2migrate:merchants`

# Migrate/Sync Giftcodes
`php artisan v2migrate:giftcodes`

# Migrate/Sync invoice-journal-events
`php artisan v2migrate:invoice-journal-events`

# Migrate Users
`php artisan v2migrate:users`

# Migrate PhysicalOrders
`php artisan v2migrate:physicalorders`

## Migrations

# Creating Migrations
`php artisan make:migration create_some_table`

# Create Table and Migrations as same time
`php artisan make:model -m DomainProgram`

# Running Migrations
`php artisan migrate`

# Rolling Back Migrations
To roll back the latest migration operation, you may use the rollback Artisan command. This command rolls back the last "batch" of migrations, which may include multiple migration files:
`php artisan migrate:rollback`

You may roll back a limited number of migrations by providing the step option to the rollback command. For example, the following command will roll back the last five migrations:

`php artisan migrate:rollback --step=5`

### Permissions updates

Install `Spatie/laravel-permission` package:
`composer require spatie/laravel-permission`

Seed permissions"

`php artisan db:seed --class=RoleSeeder` 
`php artisan db:seed --class=PermissionSeeder`

Please check: https://spatie.be/docs/laravel-permission/v5/introduction

## Seeding Database

Seeding should only be tried in a development envoirnment and never be run on a production server
`php artisan db:seed`

# Creating new seeders

Create new seeder in database/seeders folder and include seeder in DatabaseSeeder class. For example:

`public function run()
{
    $this->call([
        PermissionSeeder::class
    ]);
}`

A seeder can also be run individually:
`php artisan db:seed --class=UserSeeder`

# Validating incoming form requests

Every incoming form request must be validated using Laravel's Form Request Validation feature only. Manual validation in controller should be avoided in favor of https://laravel.com/docs/8.x/validation#form-request-validation

## Ignoring files from GIT

Add common file uploads to .gitignore file. For example:
`/public/uploads`

## Update Log

** Sep 01 2023

Run `seeders`
    `php artisan db:seed --class=EventIconSeeder`

** Aug 08 2023

Install Authorize.net package
    `php composer require authorizenet/authorizenet`

** Feb 07 2023

`php artisan db:seed --class=UpdateProgramTemplatesTable`

** January Jan 31 2023

Run `migrations`
`php artisan migrate`
    - Creates `goal_plans`, `goal_plan_types`, `expiration_rules`, `user_goals`, `email_template_types` tables

Run `seeders`
    `php artisan db:seed --class=ExpirationRuleSeeder`
    `php artisan db:seed --class=GoalPlanTypeSeeder`

** December 24, 2022
`php artisan db:seed --class=CsvImportTypeSeeder`
`php artisan db:seed --class=EmailTemplateTypeSeeder`

** August 26, 2022

php artisan db:seed --class=CountrySeeder
php artisan db:seed --class=StateSeeder

** July 27, 2022

Run `migrations`
`php artisan migrate`
    - Creates `invoices`, `invoice_types`, `invoice_journal_event`, `payment_methods` tables

Run `seeders`
    `php artisan db:seed --class=InvoiceTypeSeeder`
    `php artisan db:seed --class=PaymentMethodSeeder`

** July 22 2022
Run `migrations`
`php artisan migrate`
    - Creates `leaderboards`, `leaderboard_types`, `leaderboard_event`, `leaderboard_goal_plan`, `leaderboard_journal_event` tables

Run `seeders`
    `php artisan db:seed --class=LeaderboardTypeSeeder`

** July 06 2022

Install adjacency-list
`composer install staudenmeir/laravel-adjacency-list`

Run migrations

`php artisan migrate`

** May 20, 2022

`php artisan migrate`

`php artisan db:seed --class=OwnerSeeder`
`php artisan db:seed --class=AccountHolderPatcher`
`php artisan db:seed --class=CallbackTypeSeeder`

** Apr 28, 2022

To update db run

`php artisan migrate`

Seeders were added

`php artisan db:seed --class=AccountTypeSeeder`
`php artisan db:seed --class=CurrencySeeder`
`php artisan db:seed --class=StatusSeeder`

** Apr 20, 2022

Various new db table and changes were added. 

To update db run

`php artisan migrate`

Seeders were added

`php artisan db:seed --class=EventTypeSeeder`
`php artisan db:seed --class=JournalEventTypeSeeder`
`php artisan db:seed --class=FinanceTypeSeeder`
`php artisan db:seed --class=MediumTypeSeeder`

** Feb 22, 2022

### Permission updates

Install `Spatie/laravel-permission` package:
`composer require spatie/laravel-permission`

Remove old permission tables and create new ones:

Migration files:

1. 2022_02_21_155259_delete_old_permissions.php
2. 2022_02_22_065911_create_permission_tables.php

`php artisan migrate`

Seed:

`php artisan db:seed --class=RoleSeeder` 
`php artisan db:seed --class=PermissionSeeder`

Visit https://spatie.be/docs/laravel-permission/v5/introduction for more info about the package


## v2 to v3 Migrations

*** IMPORTANT - Following the following ORDER of migration is IMPORTANT.

** Migrate Owners

Owners and SuperAdmins should be migrated before running any other migration.

`php artisan v2migrate:owners`

** Migrating Merchants

`php artisan v2migrate:merchants`

NOTE: Merchant migration should be done before migrating programs

** Migrating by program(s)

*** This is the only option right now.

`php artisan v2migrate:program [v2 account_holder_id of root program]`

Running this program will fetch the nested tree of the given program. The import would include:
 - Domains (Domain of root program only)
 - Accounts
 - Users (Program users)
 - Invoices
 - Events
 - ProgramAccounts

** Migrating Users by Program

By Single Program
`php artisan v2migrate:users --program=288308`
By Multiple Programs
`php artisan v2migrate:users --program=288308,601858`

** Migrating giftcodes

`php artisan v2migrate:giftcodes`

** Migrating journalevents

`php artisan v2migrate:journalevents --type=merchants`
`php artisan v2migrate:journalevents --type=programs`
`php artisan v2migrate:journalevents --type=users`

** v2-v3 Migration References

- Please check app/Jobs/v2migrate for migration jobs
- Please check app/Services/v2migrate for service classes

** Push Notifications

*** Program Notification Example

`$program = Program::find(4786);
(new App\Services\PushNotificationService)->notifyUsersByProgram( $program, [
    'title'=>"You have a program notification",
    'body'=>"This is the body of program notification",
    'data'=>[ //to be consumed by the mobile app
        'param1'=>'some value',
        'param2'=>'some value',
    ]
]);`

*** User Notification Example
`$user = User::find(124);
(new App\Services\PushNotificationService)->notifySingleUser( $user, [
    'title'=>"You have a notification",
    'body'=>"This is the body of notification",
    'data'=>[ //to be consumed by the mobile app
        'param1'=>'some value',
        'param2'=>'some value',
    ]
]);`

*** Notify Multiple users by id; Example
`$userIds = [124, 125];
(new App\Services\PushNotificationService)->notifyMultipleUsers( $userIds, [
    'title'=>"You have a notification",
    'body'=>"This is the body of notification",
    'data'=>[ //to be consumed by the mobile app
        'param1'=>'some value',
        'param2'=>'some value',
    ]
]);`

One can add more methods to App\Services\PushNotificationService
