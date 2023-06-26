<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIndexesForV3Migrations1 extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        /**************** V3 updates **************/
        Schema::table('merchants', function (Blueprint $table) {
            $table->index('v2_account_holder_id');
        });

        /**************** V2 updates **************/
        Schema::connection('v2')->table('medium_info', function($table) {
            $table->index('purchase_date');
            $table->index('redemption_date');
            $table->index('cost_basis');
            $table->index('discount');
            $table->index('sku_value');
            $table->index('code');
            $table->index('pin');
            $table->index('redemption_url');
            $table->index('v3_medium_info_id');
        });
        Schema::connection('v2')->table('merchants', function($table) {
            $table->index('v3_merchant_id');
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
        Schema::table('merchants', function (Blueprint $table) {
            $table->dropIndex('merchants_v2_account_holder_id_index');
        });
        /**************** V2 updates **************/
        Schema::connection('v2')->table('medium_info', function($table) {
            $table->dropIndex('medium_info_purchase_date_index');
            $table->dropIndex('medium_info_redemption_date_index');
            $table->dropIndex('medium_info_cost_basis_index');
            $table->dropIndex('medium_info_discount_index');
            $table->dropIndex('medium_info_sku_value_index');
            $table->dropIndex('medium_info_code_index');
            $table->dropIndex('medium_info_pin_index');
            $table->dropIndex('medium_info_redemption_url_index');
            $table->dropIndex('medium_info_v3_medium_info_id_index');
        });
        Schema::connection('v2')->table('merchants', function($table) {
            $table->dropIndex('merchants_v3_merchant_id_index');
        });
    }
}
