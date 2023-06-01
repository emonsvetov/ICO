<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddV2V3MigrationMediumInfoFields extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /**************** V3 updates **************/
        Schema::table('medium_info', function (Blueprint $table) {
            $table->integer('v2_medium_info_id')->nullable();
        });
        /**************** V2 updates **************/
        Schema::connection('v2')->table('medium_info', function($table) {
            $table->boolean('purchased_by_v3')->default(0);
            $table->integer('v3_medium_info_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
         /**************** V3 updates **************/
         Schema::table('medium_info', function (Blueprint $table) {
            $table->dropColumn('v2_medium_info_id');
        });
        /**************** V2 updates **************/
        Schema::connection('v2')->table('medium_info', function($table) {
            $table->dropColumn('purchased_by_v3');
            $table->dropColumn('v3_medium_info_id');
        });
    }
}
