<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddBirthdayBadgeToEventTypesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if( !DB::table('event_types')->where('name', 'LIKE', 'Birthday Badge')->first() )  {
            DB::table('event_types')->insert(
                array(
                    'name' => 'Birthday Badge',
                    'type' => 'birthday badge',
                    'description' => 'Badge on Birthday',
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
