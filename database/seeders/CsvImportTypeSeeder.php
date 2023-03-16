<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

use App\Models\CsvImportType;

class CsvImportTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $types = [
            [
                'id' => 1,
                'context' => 'Events',
                'name' => 'Add Events',
                'type' => 'add_events',
            ],
            [
                'id' => 2,
                'context' => 'Users',
                'name' => 'Add Managers',
                'type' => 'add_managers',
            ],
            [
                'id' => 3,
                'context' => 'Users',
                'name' => 'Add Participants',
                'type' => 'add_participants',
            ],
            [
                'id' => 4,
                'context' => 'Users',
                'name' => 'Update Participants',
                'type' => 'update_participants',
            ],
            [
                'id' => 5,
                'context' => 'Users',
                'name' => 'Deactivate Participants',
                'type' => 'deactivate_participants',
            ],
            [
                'id' => 6,
                'context' => 'Programs',
                'name' => 'Add Programs',
                'type' => 'add_programs',
            ],
            [
                'id' => 7,
                'context' => 'Users',
                'name' => 'Add and Award Users',
                'type' => 'add_and_award_users',
            ],
            [
                'id' => 8,
                'context' => 'Users',
                'name' => 'Award Users',
                'type' => 'award_users',
            ]
        ];

        foreach ($types as $type) 
        {
            CsvImportType::updateOrCreate(['id' => $type['id']], $type);
        }
    }
}
