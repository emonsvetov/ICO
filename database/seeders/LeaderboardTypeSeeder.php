<?php
  
namespace Database\Seeders;
  
use Illuminate\Database\Seeder;
use App\Models\LeaderboardType;

class LeaderboardTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        LeaderboardType::insert([
            [
                "id" => 1,
                "name" => "Event Summary",
                "created_at" => now(),
                "updated_at" => now()
            ],
            [
                "id" => 2,
                "name" => "Event Volume",
                "created_at" => now(),
                "updated_at" => now()
            ],
            [
                "id" => 3,
                "name" => "Goal Progress",
                "created_at" => now(),
                "updated_at" => now()
            ]
        ]);
    }
}