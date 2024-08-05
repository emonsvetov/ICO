<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PositionPermission;

class PositionPermissionsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
		$current_datetime = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
       PositionPermission::insert([
            ['name' => 'Award Read', 'title' => 'Award Read',"created_at" => $current_datetime,"updated_at" => $current_datetime],
            ['name' => 'Award Create', 'title' => 'Award Create',"created_at" => $current_datetime,"updated_at" => $current_datetime],
			['name' => 'Award Approve', 'title' => 'Award Approve',"created_at" => $current_datetime,"updated_at" => $current_datetime],
			['name' => 'Budget Setup Create', 'title' => 'Budget Setup Create',"created_at" => $current_datetime,"updated_at" => $current_datetime],
			['name' => 'Manage Budget', 'title' => 'Manage Budget',"created_at" => $current_datetime,"updated_at" => $current_datetime],
			['name' => 'Budget Close', 'title' => 'Budget Close',"created_at" => $current_datetime,"updated_at" => $current_datetime],
			['name' => 'Budget Read', 'title' => 'Budget Read',"created_at" => $current_datetime,"updated_at" => $current_datetime],
			['name' => 'Budget Setup Edit', 'title' => 'Budget Setup Edit',"created_at" => $current_datetime,"updated_at" => $current_datetime],
            // Add more dummy data as needed
        ]);
    }
}
