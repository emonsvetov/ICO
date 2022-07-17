<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\GoalPlanType;

class GoalPlanTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        GoalPlanType::insert([
            [
                'id' => 1,
                'name' => 'Sales Goal',
                'description' => 'Sales Goal',
            ],
            [
                'id' => 2,
                'name' => 'Personal Goal',
                'description' => 'Personal Goal',
            ],
            [
                'id' => 3,
                'name' => 'Recognition Goal',
                'description' => 'Recognition Goal',
            ],
            [
                'id' => 4,
                'name' => 'Event Count Goal',
                'description' => 'Event Count Goal',
            ]
        ]);
    }
}
