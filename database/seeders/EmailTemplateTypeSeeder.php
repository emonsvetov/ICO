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
                "type" => "Invite Participant"
            ],
            [
                "id" => 2,
                "name" => "Invite Manager"
            ],
            [
                "id" => 3,
                "name" => "Welcome"
            ],
            [
                "id" => 4,
                "name" => "Password Reset"
            ],
            [
                "id" => 5,
                "name" => "Activation Reminder"
            ],
            [
                "id" => 6,
                "name" => "Award"
            ],
            [
                "id" => 7,
                "name" => "Gift Code"
            ],
            [
                "id" => 8,
                "name" => "Goal Progress"
            ],
            [
                "id" => 9,
                "name" => "Points Expiration"
            ],
            [
                "id" => 10,
                "name" => "Allocate Peer to Peer"
            ],
            [
                "id" => 11,
                "name" => "Award Badge"
            ],
            [
                "id" => 12,
                "name" => "Award Approved"
            ],
            [
                "id" => 13,
                "name" => "Award Approval"
            ],
            [
                "id" => 14,
                "name" => "Budget Approved"
            ],
            [
                "id" => 15,
                "name" => "Budget Approval"
            ],
            [
                "id" => 16,
                "name" => "Budget Denied"
            ],
            [
                "id" => 17,
                "name" => "Budget Approved Edited"
            ],
            [
                "id" => 18,
                "name" => "Program Closing Notice"
            ]
        ]);
    }
}