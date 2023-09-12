<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\EventIcon;

class EventIconSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        EventIcon::insert([
            [
                'name' => 'Congrates',
                'organization_id' => 0,
                'path' => 'eventIcons/default/1.jpg',
                'created_at' => now()
            ],
            [
                'name' => 'Goal',
                'organization_id' => 0,
                'path' => 'eventIcons/default/2.jpg',
                'created_at' => now()
            ],
            [
                'name' => 'Sorry',
                'organization_id' => 0,
                'path' => 'eventIcons/default/3.jpg',
                'created_at' => now()
            ],
            [
                'name' => 'Gift',
                'organization_id' => 0,
                'path' => 'eventIcons/default/4.jpg',
                'created_at' => now()
            ],
            [
                'name' => 'Star',
                'organization_id' => 0,
                'path' => 'eventIcons/default/5.jpg',
                'created_at' => now()
            ],
            [
                'name' => 'Winner',
                'organization_id' => 0,
                'path' => 'eventIcons/default/6.jpg',
                'created_at' => now()
            ],
        ]);
    }
}
