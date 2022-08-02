<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\PaymentMethod;

class PaymentMethodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        PaymentMethod::insert([
            [
                "id" => 1,
                "name" => 'Check',
                "description" => "Payment method is check.",
                "created_at" => now()
            ],
            [
                "id" => 2,
                "name" => 'ACH',
                "description" => "Payment method is ACH.",
                "created_at" => now()
            ],
            [
                "id" => 3,
                "name" => 'Wire Transfer',
                "description" => "Payment method is Wire Transfer.",
                "created_at" => now()
            ],
            [
                "id" => 4,
                "name" => 'Credit Card',
                "description" => "Payment method is Credit Card.",
                "created_at" => now()
            ]
        ]);
    }
}
