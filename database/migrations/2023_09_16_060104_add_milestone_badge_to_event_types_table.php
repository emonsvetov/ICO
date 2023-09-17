<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;

class AddMilestoneBadgeToEventTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if( !DB::table('event_types')->where('name', 'LIKE', 'Milestone Badge')->first() )  {
            DB::table('event_types')->insert(
                array(
                    'name' => 'Milestone Badge',
                    'type' => 'milestone badge',
                    'description' => 'Milestone Badge for Work/Joining Anniversary',
                )
            );
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //I wont delete it, rather delete manually
    }
}
