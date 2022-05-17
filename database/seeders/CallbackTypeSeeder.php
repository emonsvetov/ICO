<?php
  
namespace Database\Seeders;
  
use Illuminate\Database\Seeder;
use App\Models\CallbackType;

class CallbackTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        CallbackType::insert([
            [
                'id' => 1,
                'type' => 'Goal Met',
                'description' => 'Sales goal target was met within the defined time period.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'type' => 'Goal Exceeded',
                'description' => 'Sales goal target was exceeded within the defined time period.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'type' => 'B2B Gift Code',
                'description' => 'Merchant needs to call a 3rd party in order acquire a gife code for redemption.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'type' => 'Invite Participant',
                'description' => 'Calls out to an external system when a participant is added.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 5,
                'type' => 'Award Points to Participant',
                'description' => 'Calls out to an external system when a participant is awarded points.',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 6,
                'type' => 'peer2peer badge',
                'description' => 'Badges awarded to participants from other participants',
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}