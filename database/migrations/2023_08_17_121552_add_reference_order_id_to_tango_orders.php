<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReferenceOrderIdToTangoOrders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('tango_orders', function (Blueprint $table) {
            $table->renameColumn('external_id', 'reference_order_id');
        });

        Schema::table('medium_info', function (Blueprint $table) {
            $table->string('tango_reference_order_id', 255)->nullable()
                ->after('tango_request_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('tango_orders', function (Blueprint $table) {
            $table->renameColumn('reference_order_id', 'external_id');
        });

        Schema::table('medium_info', function (Blueprint $table) {
            $table->dropColumn(['tango_reference_order_id']);
        });
    }
}
