<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTangoOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('tango_orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('physical_order_id');
            $table->unsignedBigInteger('program_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('merchant_id');
            $table->string('external_id', 255)->comment('order id from Tango')->nullable();
            $table->string('request_id', 255)->nullable();;
            $table->boolean('status')->nullable();
            $table->mediumText('log')->nullable();
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
        Schema::dropIfExists('tango_orders');
    }
}
