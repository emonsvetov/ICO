<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddBirthdayAwardToEventTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if( !DB::table('event_types')->where('name', 'LIKE', 'Birthday Award')->first() )  {
            DB::table('event_types')->insert(
                array(
                    'name' => 'Birthday Award',
                    'type' => 'Birthday Award',
                    'description' => 'Award on Birthday',
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
    }
}
