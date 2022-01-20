<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMediumInfoTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('medium_info', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->date('purchase_date');
            $table->date('redemption_date');
            $table->date('expiration_date');
            $table->timestamp('hold_until');
            $table->decimal('redemption_value');
            $table->decimal('cost_basis');
            $table->decimal('discount');
            $table->decimal('sku_value');
            $table->string('pin');
            $table->string('redemption_url');
            $table->string('encryption');
            $table->string('code');
            $table->integer('merchant_account_holder_id');
            $table->integer('redeemed_program_account_id');
            $table->integer('redeemed_account_holder_id');
            $table->integer('redeemed_merchant_account_holder_id');
            $table->integer('factor_valuation');
            $table->tinyInteger('medium_info_is_test');
            $table->dateTime('redemtion_datetime');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('medium_info');
    }
}
