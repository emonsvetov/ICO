<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\JournalEventType;

class JournalEventTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        JournalEventType::insert([
            [
                "id" => 1,
                "type" => "Accrue monies by recipient"
            ],
            [
                "id" => 2,
                "type" => "Accrue pending points by recipient"
            ],
            [
                "id" => 22,
                "type" => "Add points to program"
            ],
            [
                "id" => 48,
                "type" => "Allocate peer points to recipient"
            ],
            [
                "id" => 3,
                "type" => "Award monies to recipient"
            ],
            [
                "id" => 4,
                "type" => "Award points to recipient"
            ],
            [
                "id" => 5,
                "type" => "Bill bill program on point redemption"
            ],
            [
                "id" => 6,
                "type" => "Bill program on point award"
            ],
            [
                "id" => 7,
                "type" => "Bill program on point purchase"
            ],
            [
                "id" => 40,
                "type" => "Charge program for admin fee"
            ],
            [
                "id" => 35,
                "type" => "Charge program for convenience fee"
            ],
            [
                "id" => 27,
                "type" => "Charge program for deposit fee"
            ],
            [
                "id" => 47,
                "type" => "Charge program for fixed fee"
            ],
            [
                "id" => 26,
                "type" => "Charge program for monies pending"
            ],
            [
                "id" => 52,
                "type" => "Charge program for monthly usage fee"
            ],
            [
                "id" => 25,
                "type" => "Charge setup fee to program"
            ],
            [
                "id" => 44,
                "type" => "Deactivate monies"
            ],
            [
                "id" => 45,
                "type" => "Deactivate points"
            ],
            [
                "id" => 8,
                "type" => "Deposit monies for program awards"
            ],
            [
                "id" => 9,
                "type" => "Expire monies"
            ],
            [
                "id" => 10,
                "type" => "Expire points"
            ],
            [
                "id" => 24,
                "type" => "Owner refunds points to program"
            ],
            [
                "id" => 58,
                "type" => "Premium cost to program"
            ],
            [
                "id" => 11,
                "type" => "Program makes a payment"
            ],
            [
                "id" => 42,
                "type" => "Program pays for admin fee"
            ],
            [
                "id" => 36,
                "type" => "Program pays for convenience fee"
            ],
            [
                "id" => 29,
                "type" => "Program pays for deposit fee"
            ],
            [
                "id" => 49,
                "type" => "Program pays for fixed fee"
            ],
            [
                "id" => 28,
                "type" => "Program pays for monies pending"
            ],
            [
                "id" => 53,
                "type" => "Program pays for monthly usage fee"
            ],
            [
                "id" => 23,
                "type" => "Program pays for points"
            ],
            [
                "id" => 46,
                "type" => "Program pays for setup fee"
            ],
            [
                "id" => 61,
                "type" => "Program refunds for monies pending"
            ],
            [
                "id" => 32,
                "type" => "Program transfers monies available"
            ],
            [
                "id" => 60,
                "type" => "Promotional Award Points"
            ],
            [
                "id" => 12,
                "type" => "Purchase gift codes for monies"
            ],
            [
                "id" => 34,
                "type" => "Reclaim monies"
            ],
            [
                "id" => 50,
                "type" => "Reclaim peer points"
            ],
            [
                "id" => 38,
                "type" => "Reclaim points"
            ],
            [
                "id" => 13,
                "type" => "Redeem monies for gift codes"
            ],
            [
                "id" => 14,
                "type" => "Redeem points for gift codes"
            ],
            [
                "id" => 54,
                "type" => "Redeem points for international shopping"
            ],
            [
                "id" => 59,
                "type" => "Redeemable by Internal Store"
            ],
            [
                "id" => 15,
                "type" => "Refund monies for points to program"
            ],
            [
                "id" => 16,
                "type" => "Refund monies to recipient for gift codes"
            ],
            [
                "id" => 17,
                "type" => "Refund points redeemed to recipient"
            ],
            [
                "id" => 41,
                "type" => "Refund program for monies transaction fee"
            ],
            [
                "id" => 18,
                "type" => "Return deposited monies to program"
            ],
            [
                "id" => 19,
                "type" => "Return gift codes to merchant for monies"
            ],
            [
                "id" => 20,
                "type" => "Return pending points to program from recipient"
            ],
            [
                "id" => 21,
                "type" => "Revenue Share"
            ],
            [
                "id" => 43,
                "type" => "Reversal program pays for admin fee"
            ],
            [
                "id" => 37,
                "type" => "Reversal program pays for convenience fee"
            ],
            [
                "id" => 33,
                "type" => "Reversal program pays for deposit fee"
            ],
            [
                "id" => 51,
                "type" => "Reversal program pays for fixed fee"
            ],
            [
                "id" => 31,
                "type" => "Reversal program pays for monies pending"
            ],
            [
                "id" => 30,
                "type" => "Reversal program pays for points"
            ],
            [
                "id" => 39,
                "type" => "Transfer gift codes"
            ],
            [
                "id" => 56,
                "type" => "VOID Charge program for convenience fee"
            ],
            [
                "id" => 57,
                "type" => "VOID Charge program for deposit fee"
            ],
            [
                "id" => 55,
                "type" => "VOID Charge program for monies pending"
            ]
        ]);
    }
}
