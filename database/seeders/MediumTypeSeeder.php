<?php
  
namespace Database\Seeders;
  
use Illuminate\Database\Seeder;
use App\Models\MediumType;

class MediumTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        MediumType::insert([
            [
                'id' => 1,
                'name' => 'Gift Codes'
            ],
            [
                'id' => 2,
                'name' => 'Monies'
            ],
            [
                'id' => 3,
                'name' => 'Points'
            ]
        ]);
    }
}