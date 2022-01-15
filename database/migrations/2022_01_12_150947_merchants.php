<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Merchants extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('merchants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('logo', 165);
            $table->string('icon', 165);
            $table->string('large_icon', 165)->nullable();
            $table->string('banner', 165)->nullable();
            $table->mediumText('description');
            $table->string('website')->nullable();
            $table->mediumText('redemption_instruction');
            $table->integer('redemption_callback_id')->nullable();
            $table->string('category')->nullable();
            $table->string('merchant_code', 3);
            $table->boolean('website_is_redemption_url')->nullable();
            $table->boolean('get_gift_codes_from_root')->nullable();
            $table->boolean('is_default')->nullable();
            $table->boolean('giftcodes_require_pin')->nullable();
            $table->integer('display_rank_by_priority')->nullable();
            $table->integer('display_rank_by_redemptions')->nullable();
            $table->boolean('requires_shipping')->nullable();
            $table->boolean('physical_order')->nullable();
            $table->boolean('is_premium')->nullable();
            $table->boolean('use_tango_api')->nullable();
            $table->integer('toa_id')->nullable();
            $table->smallInteger('status')->nullable();
            $table->boolean('display_popup')->nullable();
            $table->boolean('deleted')->nullable();

            $table->timestamps();

            $table->index('id');
            $table->index('name');
            $table->index('merchant_code');
            $table->index('display_rank_by_priority');
            $table->index('display_rank_by_redemptions');
            $table->index('is_premium');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
