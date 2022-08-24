<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\InvoiceType;

class InvoiceTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        InvoiceType::insert([
            [
                "id" => 1,
                "name" => 'Monthly',
                "description" => "Invoices are sent on the 1st of each month for the previous calendar month.",
                "created_at" => now()
            ],
            [
                "id" => 2,
                "name" => 'Pre-Paid',
                "description" => "Customers pre-pay for a number of points that are immediately awarded to participants.",
                "created_at" => now()
            ],
            [
                "id" => 3,
                "name" => 'Bill To Parent Program',
                "description" => "No invoice is generated for sub-programs. The parent program itemizes sub-program invoices.",
                "created_at" => now()
            ],
            [
                "id" => 4,
                "name" => 'Bill to Parent',
                "description" => "[not implemented] No invoice is generated for sub-programs. The parent program itemizes sub-program invoices.",
                "created_at" => now()
            ],
            [
                "id" => 5,
                "name" => 'On-Demand',
                "description" => "Customer requests an invoice for a dollar amount they specify to purchase monies in the program.",
                "created_at" => now()
            ],
            [
                "id" => 6,
                "name" => 'Credit Card Deposit',
                "description" => "Credit Card Deposit",
                "created_at" => now()
            ]
        ]);
    }
}
