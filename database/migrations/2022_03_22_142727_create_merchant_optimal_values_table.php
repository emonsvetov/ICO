<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMerchantOptimalValuesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('merchant_optimal_values', function (Blueprint $table) {
            $table->id();
            $table->integer('merchant_id');
            $table->double('denomination');
            $table->integer('optimal_value');
            $table->timestamps();

            $table->index('merchant_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('merchant_optimal_values');
    }
}
