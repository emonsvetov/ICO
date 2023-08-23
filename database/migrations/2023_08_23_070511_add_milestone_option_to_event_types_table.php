<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddMilestoneOptionToEventTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if( !DB::table('event_types')->where('name', 'LIKE', 'Milestone Award')->first() )  {
            DB::table('event_types')->insert(
                array(
                    'name' => 'Milestone Award',
                    'type' => 'milestone award',
                    'description' => 'Milestone Award for Work/Joining Anniversary',
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
        DB::table('event_types')->where('name', 'LIKE', 'Milestone Award')->delete();
    }
}
