<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ExpirationRule;

class ExpirationRuleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        ExpirationRule::insert([
            [
                'id' => 1,
                'name' => '12 Months',
                'expire_offset'=>12,
                'expire_units'=>'month',
                'description' => 'Expiration date is set to 12-months.',
            ],
            [
                'id' => 2,
                'name' => '1 of Month',
                'expire_offset'=>1,
                'expire_units'=>'month',
                'description' => 'Expiration date is to 1-month.',
            ],
            [
                'id' => 3,
                'name' => 'End of Next Year',
                'expire_offset'=>0,
                'expire_units'=>null,
                'description' => 'Expiration date is the end of the following year.',
            ],
            [
                'id' => 4,
                'name' => 'Custom',
                'expire_offset'=>0,
                'expire_units'=>null,
                'description' => 'Expiration date is user specified.',
            ],
            [
                'id' => 5,
                'name' => 'Annual',
                'expire_offset'=>0,
                'expire_units'=>null,
                'description' => 'Expiration date a specified Month and Day each year.',
            ],
            [
                'id' => 6,
                'name' => 'Specified',
                'expire_offset'=>0,
                'expire_units'=>null,
                'description' => 'Expiration date is user specified.',
            ],
            [
                'id' => 7,
                'name' => '2 Years',
                'expire_offset'=>0,
                'expire_units'=>null,
                'description' => 'Expiration date is the end of the next following year.',
            ]
        ]);
    }
}


