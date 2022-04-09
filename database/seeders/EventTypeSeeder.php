<?php
  
namespace Database\Seeders;
  
use Illuminate\Database\Seeder;
use App\Models\EventType;

class EventTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        EventType::insert([
            [
                'name' => 'Standard',
                'type' => 'standard',
                'description' => 'Standard Event Type',
            ],
            [
                'name' => 'Activation',
                'type' => 'activation',
                'description' => 'Awarded automatically when a participant activates their account',
            ],
            [
                'name' => 'Peer to Peer',
                'type' => 'peer2peer',
                'description' => 'Awarded to participants from other participants',
            ],
            [
                'name' => 'Peer to Peer Allocation',
                'type' => 'peer2peer allocation',
                'description' => 'Awarded to participants from managers to the participant\'s peer account',
            ],
            [
                'name' => 'Badge',
                'type' => 'badge',
                'description' => 'Badges awarded to participants from managers to participants',
            ],
            [
                'name' => 'Peer to Peer Badge',
                'type' => 'peer2peer badge',
                'description' => 'Badges awarded to participants from other participants',
            ],
            [
                'name' => 'Promotional Award',
                'type' => 'promotional award',
                'description' => 'Promotional Award Type',
            ],
            [
                'name' => 'Auto Award',
                'type' => 'auto award',
                'description' => 'Auto Award for Birthday and Anniversary Award',
            ]
        ]);
    }
}