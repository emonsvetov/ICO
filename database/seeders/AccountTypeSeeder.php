<?php
  
namespace Database\Seeders;
  
use Illuminate\Database\Seeder;
use App\Models\AccountType;

class AccountTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        AccountType::insert([
            [
                "id" => 29,
                "name" => ""
            ],
            [
                "id" => 26,
                "name" => "ACCOUNT_TYPE_POINTS_AWARDED"
            ],
            [
                "id" => 27,
                "name" => "Award Internal Store Points"
            ],
            [
                "id" => 28,
                "name" => "Award Promotional Points"
            ],
            [
                "id" => 1,
                "name" => "Cash"
            ],
            [
                "id" => 2,
                "name" => "Gift Codes Available"
            ],
            [
                "id" => 3,
                "name" => "Gift Codes Pending"
            ],
            [
                "id" => 4,
                "name" => "Gift Codes Redeemed"
            ],
            [
                "id" => 5,
                "name" => "Gift Codes Spent"
            ],
            [
                "id" => 6,
                "name" => "Income"
            ],
            [
                "id" => 23,
                "name" => "Monies Available"
            ],
            [
                "id" => 9,
                "name" => "Monies Awarded"
            ],
            [
                "id" => 8,
                "name" => "Monies Deposits"
            ],
            [
                "id" => 20,
                "name" => "Monies Due to Owner"
            ],
            [
                "id" => 24,
                "name" => "Monies Expired"
            ],
            [
                "id" => 22,
                "name" => "Monies Fees"
            ],
            [
                "id" => 21,
                "name" => "Monies Paid to Progam"
            ],
            [
                "id" => 10,
                "name" => "Monies Payable"
            ],
            [
                "id" => 11,
                "name" => "Monies Pending"
            ],
            [
                "id" => 12,
                "name" => "Monies Receivable"
            ],
            [
                "id" => 14,
                "name" => "Monies Redeemed"
            ],
            [
                "id" => 13,
                "name" => "Monies Shared"
            ],
            [
                "id" => 7,
                "name" => "Monies Transaction"
            ],
            [
                "id" => 25,
                "name" => "Peer to Peer Points"
            ],
            [
                "id" => 15,
                "name" => "Points Available"
            ],
            [
                "id" => 16,
                "name" => "Points Awarded"
            ],
            [
                "id" => 17,
                "name" => "Points Expired"
            ],
            [
                "id" => 18,
                "name" => "Points Pending"
            ],
            [
                "id" => 19,
                "name" => "Points Redeemed"
            ]
        ]);
    }
}