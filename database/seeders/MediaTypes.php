<?php

namespace Database\Seeders;

use App\Models\ProgramMediaType;
use Illuminate\Database\Seeder;

class MediaTypes extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        ProgramMediaType::insert([
            [
                'program_media_type_id' => 1,
                'name' => 'Brochures'
            ],
            [
                'program_media_type_id' => 2,
                'name' => 'Newsletters'
            ],
            [
                'program_media_type_id' => 3,
                'name' => 'Training'
            ],
            [
                'program_media_type_id' => 4,
                'name' => 'Videos'
            ]
        ]);
    }
}
