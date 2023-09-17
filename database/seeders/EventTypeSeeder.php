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
                'id' => 1,
                'name' => 'Standard',
                'type' => 'standard',
                'description' => 'Standard Event Type',
            ],
            [
                'id' => 2,
                'name' => 'Activation',
                'type' => 'activation',
                'description' => 'Awarded automatically when a participant activates their account',
            ],
            [
                'id' => 3,
                'name' => 'Peer to Peer',
                'type' => 'peer2peer',
                'description' => 'Awarded to participants from other participants',
            ],
            [
                'id' => 4,
                'name' => 'Peer to Peer Allocation',
                'type' => 'peer2peer allocation',
                'description' => 'Awarded to participants from managers to the participant\'s peer account',
            ],
            [
                'id' => 5,
                'name' => 'Badge',
                'type' => 'badge',
                'description' => 'Badges awarded to participants from managers to participants',
            ],
            [
                'id' => 6,
                'name' => 'Peer to Peer Badge',
                'type' => 'peer2peer badge',
                'description' => 'Badges awarded to participants from other participants',
            ],
            [
                'id' => 7,
                'name' => 'Promotional Award',
                'type' => 'promotional award',
                'description' => 'Promotional Award Type',
            ],
            [
                'id' => 8,
                'name' => 'Auto Award',
                'type' => 'auto award',
                'description' => 'Auto Award for Birthday and Anniversary Award',
            ],
            [
                'id' => 9,
                'name' => 'Milestone Award',
                'type' => 'milestone award',
                'description' => 'Milestone Award for Work/Joining Anniversary',
            ],
            [
                'id' => 10,
                'name' => 'Milestone Badge',
                'type' => 'milestone badge',
                'description' => 'Milestone Badge for Work/Joining Anniversary',
            ]
        ]);
    }
}
