<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class V2V3MigrationPhysicalOrdersSocialEventXml extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /**************** v3 updates **************/
        Schema::table('physical_orders', function (Blueprint $table) {
            if( !Schema::hasColumn('physical_orders', 'v2_id')) {
                $table->bigInteger('v2_id')->nullable();
            }
        });
        Schema::table('social_wall_posts', function (Blueprint $table) {
            if( !Schema::hasColumn('social_wall_posts', 'v2_id')) {
                $table->bigInteger('v2_id')->nullable();
            }
        });
        Schema::table('event_xml_data', function (Blueprint $table) {
            if( !Schema::hasColumn('event_xml_data', 'v2_id')) {
                $table->bigInteger('v2_id')->nullable();
            }
        });

        /**************** v2 updates **************/
        Schema::connection('v2')->table('physical_orders', function($table) {
            if (!Schema::connection('v2')->hasColumn('physical_orders', 'v3_id'))    {
                $table->bigInteger('v3_id')->nullable();
            }
        });
        Schema::connection('v2')->table('social_wall_posts', function($table) {
            if (!Schema::connection('v2')->hasColumn('social_wall_posts', 'v3_id'))    {
                $table->bigInteger('v3_id')->nullable();
            }
        });
        Schema::connection('v2')->table('event_xml_data', function($table) {
            if (!Schema::connection('v2')->hasColumn('event_xml_data', 'v3_id'))    {
                $table->bigInteger('v3_id')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        /**************** v3 updates **************/
        Schema::table('physical_orders', function (Blueprint $table) {
            $table->dropColumn('v2_id');
        });
        Schema::table('social_wall_posts', function (Blueprint $table) {
            $table->dropColumn('v2_id');
        });
        Schema::table('event_xml_data', function (Blueprint $table) {
            $table->dropColumn('v2_id');
        });

        /**************** v2 updates **************/
        Schema::connection('v2')->table('physical_orders', function($table) {
            $table->dropColumn('v3_id');
        });
        Schema::connection('v2')->table('social_wall_posts', function($table) {
            $table->dropColumn('v3_id');
        });
        Schema::connection('v2')->table('event_xml_data', function($table) {
            $table->dropColumn('v3_id');
        });
    }
}
