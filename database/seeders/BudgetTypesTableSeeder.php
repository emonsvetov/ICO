<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\BudgetType;

class BudgetTypesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $current_datetime = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
       BudgetType::insert([
            ['name' => 'monthly', 'title' => 'Monthly',"created_at" => $current_datetime,"updated_at" => $current_datetime],
            ['name' => 'monthly_rollover', 'title' => 'Monthly Rollover',"created_at" => $current_datetime,"updated_at" => $current_datetime],
			['name' => 'specific_period', 'title' => 'Specific Period',"created_at" => $current_datetime,"updated_at" => $current_datetime],
			['name' => 'yearly', 'title' => 'Yearly',"created_at" => $current_datetime,"updated_at" => $current_datetime],
            // Add more dummy data as needed
        ]);
    }
}
