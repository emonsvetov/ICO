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
            $table->date('purchase_date');
            $table->date('redemption_date')->nullable();
            $table->date('expiration_date')->nullable();
            $table->timestamp('hold_until')->nullable()->default(null);
            $table->decimal('redemption_value', 18, 4);
            $table->decimal('cost_basis', 18, 4);
            $table->decimal('discount', 9, 4);
            $table->decimal('sku_value', 18, 4);
            $table->string('pin', 45)->nullable();
            $table->string('redemption_url', 2048)->nullable();
            $table->string('encryption', 45)->nullable();
            $table->string('code', 165)->nullable();
            $table->unsignedBigInteger('merchant_id')->nullable();
            $table->unsignedBigInteger('redeemed_merchant_id')->nullable();
            $table->unsignedBigInteger('redeemed_program_id')->nullable();
            $table->unsignedBigInteger('redeemed_user_id')->nullable();
            $table->integer('factor_valuation')->unsigned();
            $table->boolean('medium_info_is_test')->default(0);
            $table->dateTime('redemption_datetime')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('merchant_id');
            $table->index('redeemed_merchant_id');
            $table->index('redeemed_program_id');
            $table->index('redeemed_user_id');
            $table->index(['purchase_date', 'sku_value', 'redemption_value']);
            $table->index(['redemption_value', 'merchant_id']);

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
