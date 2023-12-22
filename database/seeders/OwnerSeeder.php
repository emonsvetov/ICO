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
        $owner = Owner::where('name', 'LIKE', 'Application Owner')->first();
        if( !$owner )    {
            $account_holder_id = AccountHolder::insertGetId(['context'=>'Owner', 'created_at' => now()]);
            $inserted = Owner::insert([
                [
                    "account_holder_id" => $account_holder_id,
                    "name" => "Application Owner",
                ],
            ]);
            if( $inserted ) {
                $owner = Owner::where('name', 'LIKE', 'Application Owner')->first();
            }
        }

        if( !empty( $owner ) )  {
            $account1 = \App\Models\Account::where([
                'account_holder_id' => $owner->account_holder_id,
                'account_type_id' => 1,
                'finance_type_id' => 1,
                'medium_type_id' => 2,
                'currency_type_id' => 1,
            ])->first();
            if( !$account1 ) {
                \App\Models\Account::insert(
                    [
                        'account_holder_id' => $owner->account_holder_id,
                        'account_type_id' => 1,
                        'finance_type_id' => 1,
                        'medium_type_id' => 2,
                        'currency_type_id' => 1,
                    ]
                );
            }
            $account2 = \App\Models\Account::where([
                'account_holder_id' => $owner->account_holder_id,
                'account_type_id' => 6,
                'finance_type_id' => 3,
                'medium_type_id' => 2,
                'currency_type_id' => 1,
            ])->first();
            if( !$account2 ) {
                \App\Models\Account::insert(
                    [
                        'account_holder_id' => $owner->account_holder_id,
                        'account_type_id' => 6,
                        'finance_type_id' => 3,
                        'medium_type_id' => 2,
                        'currency_type_id' => 1,
                    ]
                );
            }
        }
    }
}
