<?php
  
namespace Database\Seeders;
  
use Illuminate\Database\Seeder;
use App\Models\Status;

class StatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $current_datetime = \Carbon\Carbon::now()->format('Y-m-d H:i:s');
        Status::insert([
            [
                "id" => 1,
                "context" => "Users",
                "status" => "Pending Activation",
                "description" => "",
                "created_at" => $current_datetime,
                "updated_at" => $current_datetime
            ],
            [
                "id" => 2,
                "context" => "Users",
                "status" => "Active",
                "description" => "",
                "created_at" => $current_datetime,
                "updated_at" => $current_datetime
            ],
            [
                "id" => 3,
                "context" => "Users",
                "status" => "Deleted",
                "description" => "",
                "created_at" => $current_datetime,
                "updated_at" => $current_datetime
            ],
            [
                "id" => 4,
                "context" => "Users",
                "status" => "Pending Deactivation",
                "description" => "",
                "created_at" => $current_datetime,
                "updated_at" => $current_datetime
            ],
            [
                "id" => 5,
                "context" => "Users",
                "status" => "Deactivated",
                "description" => "",
                "created_at" => $current_datetime,
                "updated_at" => $current_datetime
            ],
            [
                "id" => 6,
                "context" => "Users",
                "status" => "Locked",
                "description" => "",
                "created_at" => $current_datetime,
                "updated_at" => $current_datetime
            ],
            [
                "id" => 7,
                "context" => "Goals",
                "status" => "Future",
                "description" => "",
                "created_at" => $current_datetime,
                "updated_at" => $current_datetime
            ],
            [
                "id" => 8,
                "context" => "Goals",
                "status" => "Active",
                "description" => "",
                "created_at" => $current_datetime,
                "updated_at" => $current_datetime
            ],
            [
                "id" => 9,
                "context" => "Goals",
                "status" => "Expired",
                "description" => "",
                "created_at" => $current_datetime,
                "updated_at" => $current_datetime
            ],
            [
                "id" => 10,
                "context" => "Programs",
                "status" => "Active",
                "description" => "",
                "created_at" => $current_datetime,
                "updated_at" => $current_datetime
            ],
            [
                "id" => 11,
                "context" => "Programs",
                "status" => "Deleted",
                "description" => "",
                "created_at" => $current_datetime,
                "updated_at" => $current_datetime
            ],
            [
                "id" => 12,
                "context" => "Programs",
                "status" => "Locked",
                "description" => "",
                "created_at" => $current_datetime,
                "updated_at" => $current_datetime
            ],
            [
                "id" => 13,
                "context" => "Events",
                "status" => "Active",
                "description" => "",
                "created_at" => $current_datetime,
                "updated_at" => $current_datetime
            ],
            [
                "id" => 14,
                "context" => "Events",
                "status" => "Deactivated",
                "description" => "",
                "created_at" => $current_datetime,
                "updated_at" => $current_datetime
            ],
            [
                "id" => 15,
                "context" => "Leaderboards",
                "status" => "Active",
                "description" => "",
                "created_at" => $current_datetime,
                "updated_at" => $current_datetime
            ],
            [
                "id" => 16,
                "context" => "Leaderboards",
                "status" => "Deactivated",
                "description" => "",
                "created_at" => $current_datetime,
                "updated_at" => $current_datetime
            ],
            [
                "id" => 17,
                "context" => "Leaderboards",
                "status" => "Deleted",
                "description" => "",
                "created_at" => $current_datetime,
                "updated_at" => $current_datetime
            ],
            [
                "id" => 18,
                "context" => "Users",
                "status" => "New",
                "description" => "",
                "created_at" => $current_datetime,
                "updated_at" => $current_datetime
            ],
            [
                "id" => 19,
                "context" => "Orders",
                "status" => "Pending",
                "description" => "",
                "created_at" => $current_datetime,
                "updated_at" => $current_datetime
            ],
            [
                "id" => 20,
                "context" => "Orders",
                "status" => "Shipped",
                "description" => "",
                "created_at" => $current_datetime,
                "updated_at" => $current_datetime
            ],
            [
                "id" => 21,
                "context" => "Orders",
                "status" => "Cancelled",
                "description" => "",
                "created_at" => $current_datetime,
                "updated_at" => $current_datetime
            ]
        ]);
    }
}