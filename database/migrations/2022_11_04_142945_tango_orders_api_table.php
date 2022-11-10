<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class TangoOrdersApiTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tango_orders_api', function (Blueprint $table) {
            $table->id();
            $table->string('platform_name', 255)->nullable();
            $table->string('platform_key', 255)->nullable();
            $table->string('platform_url', 255)->nullable();
            $table->string('platform_mode', 255)->nullable();
            $table->string('account_identifier', 255)->nullable();
            $table->string('account_number', 255)->nullable();
            $table->string('customer_number', 255)->nullable();
            $table->string('udid', 255)->nullable();
            $table->string('etid', 255)->nullable();
            $table->string('status')->boolean()->default(1);
            $table->unsignedBigInteger('user_id');
            $table->string('name', 255)->nullable();
            $table->string('is_test')->boolean()->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('tango_orders_api');
    }
}
