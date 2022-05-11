<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Owner;

class OwnerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        Owner::insert([
            [
                "account_holder_id" => 1,
                "name" => "Application Owner",
            ],
        ]);
    }
}
