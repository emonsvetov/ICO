<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TangoVirtualInventory extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        //Adding new column create_invoices in programs

        Schema::table('merchants', function (Blueprint $table) {
            $table->boolean('use_virtual_inventory')->default(0)->after('use_tango_api');
            $table->string( 'virtual_denominations', 255)->nullable()->after('use_virtual_inventory');
            $table->decimal( 'virtual_discount', 18, 4)->nullable()->after('virtual_denominations');

        });

        Schema::table('tango_orders_api', function (Blueprint $table) {
            $table->integer('toa_merchant_min_value')->default(0);
            $table->integer('toa_merchant_max_value')->nullable()->default(0)->after('toa_merchant_min_value');
        });

        Schema::table('medium_info', function (Blueprint $table) {
            $table->boolean('virtual_inventory')->default(0);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('merchants', function (Blueprint $table) {
            $table->dropColumn('use_virtual_inventory');
            $table->dropColumn('virtual_denominations');
            $table->dropColumn('virtual_discount');
        });

        Schema::table('tango_orders_api', function (Blueprint $table) {
            $table->dropColumn('toa_merchant_min_value');
            $table->dropColumn('toa_merchant_max_value');
        });

        Schema::table('medium_info', function (Blueprint $table) {
            $table->dropColumn('virtual_inventory');
        });
    }
}
