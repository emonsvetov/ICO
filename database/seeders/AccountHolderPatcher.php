<?php
  
namespace Database\Seeders;
  
use Illuminate\Database\Seeder;
use App\Models\Program;
use App\Models\Merchant;
use App\Models\Owner;
use App\Models\User;
use App\Models\AccountHolder;

class AccountHolderPatcher extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        foreach( Owner::all() as $owner ) {
            $account_holder_id = AccountHolder::insertGetId(['context'=>'Owner', 'created_at' => now()]);
            $owner->account_holder_id = $account_holder_id;
            $owner->save();
        }
        foreach( User::all() as $user ) {
            $account_holder_id = AccountHolder::insertGetId(['context'=>'User', 'created_at' => now()]);
            $user->account_holder_id = $account_holder_id;
            $user->save();
        }
        foreach( Merchant::all() as $merchant ) {
            $account_holder_id = AccountHolder::insertGetId(['context'=>'Merchant', 'created_at' => now()]);
            $merchant->account_holder_id = $account_holder_id;
            $merchant->save();
        }
        foreach( Program::all() as $program ) {
            $account_holder_id = AccountHolder::insertGetId(['context'=>'Program', 'created_at' => now()]);
            $program->account_holder_id = $account_holder_id;
            $program->save();
        }
    }
}