<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class UpdateProgramTemplatesTable extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        \App\Models\ProgramTemplate::where('name', 'LIKE', 'Original')->update(['name'=>'Clear']);
        \App\Models\ProgramTemplate::where('name', 'LIKE', 'New')->update(['name'=>'Classic']);
    }
}
