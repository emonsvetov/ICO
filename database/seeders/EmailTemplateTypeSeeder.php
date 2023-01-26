<?php
  
namespace Database\Seeders;
  
use Illuminate\Database\Seeder;
use App\Models\EmailTemplateType;

class EmailTemplateTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        EmailTemplateType::insert([
            [
                "id" => 1,
                "type" => "Activation Reminder"
            ],
            [
                "id" => 2,
                "name" => "Award"
            ],
            [
                "id" => 3,
                "name" => "Award Badge"
            ],
            [
                "id" => 4,
                "name" => "Gift Code"
            ],
            [
                "id" => 5,
                "name" => "Goal Status"
            ],
            [
                "id" => 6,
                "name" => "Invite Manager"
            ],
            [
                "id" => 7,
                "name" => "Invite Participant"
            ],
            [
                "id" => 8,
                "name" => "Password Reset"
            ],
            [
                "id" => 9,
                "name" => "Peer Allocation"
            ],
            [
                "id" => 10,
                "name" => "Peer Award"
            ],
            [
                "id" => 11,
                "name" => "Reward Expiration Notice"
            ],
            [
                "id" => 12,
                "name" => "Welcome"
            ]
        ]);
    }
}