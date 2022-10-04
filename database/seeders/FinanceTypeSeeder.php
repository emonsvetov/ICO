<?php
  
namespace Database\Seeders;
  
use Illuminate\Database\Seeder;
use App\Models\FinanceType;

class FinanceTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        FinanceType::insert([
            [
                'id' => 1,
                'name' => 'Asset'
            ],
            [
                'id' => 2,
                'name' => 'Liability'
            ],
            [
                'id' => 3,
                'name' => 'Revenue'
            ]
        ]);
    }
}