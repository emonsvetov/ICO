<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddCustomTypeToEventTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if( !DB::table('event_types')->where('name', 'LIKE', 'Custom')->first() )  {
            DB::table('event_types')->insert(
                array(
                    'name' => 'Custom',
                    'type' => 'custom',
                    'description' => 'Custom Award Type',
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
