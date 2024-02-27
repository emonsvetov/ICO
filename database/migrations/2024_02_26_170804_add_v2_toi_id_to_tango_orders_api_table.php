<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddV2ToiIdToTangoOrdersApiTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tango_orders_api', function (Blueprint $table) {
            $table->integer('v2_toa_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tango_orders_api', function (Blueprint $table) {
            $table->dropColumn('v2_toa_id');
        });
    }
}
