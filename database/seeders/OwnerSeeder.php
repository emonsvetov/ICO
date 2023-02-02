<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AccountHolder;
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
        $account_holder_id = AccountHolder::insertGetId(['context'=>'Owner', 'created_at' => now()]);
        Owner::insert([
            [
                "account_holder_id" => $account_holder_id,
                "name" => "Application Owner",
            ],
        ]);
    }
}
