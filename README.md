## About Incentco

[description here]

## Common Conventions

Use Singuler name for Controller name. For example `MerchantController.php`

## make Controller (for API), Model & Form Requests

`php artisan make:controller DomainController --api --model=Domain --requests`

## Create policy class for a given model

`php artisan make:policy MerchantPolicy --model=Merchant`

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

** Feb 22, 2022 - START

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

-- Feb 22, 2022 - END