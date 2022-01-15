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
            $table->string('logo', 165)->nullable();
            $table->string('icon', 165)->nullable();
            $table->string('large_icon', 165)->nullable();
            $table->string('banner', 165)->nullable();
            $table->mediumText('description')->nullable();
            $table->string('website')->nullable();
            $table->mediumText('redemption_instruction')->nullable();
            $table->integer('redemption_callback_id')->nullable();
            $table->string('category')->nullable();
            $table->string('merchant_code', 3);
            $table->boolean('website_is_redemption_url');
            $table->boolean('get_gift_codes_from_root');
            $table->boolean('is_default');
            $table->boolean('giftcodes_require_pin');
            $table->integer('display_rank_by_priority');
            $table->integer('display_rank_by_redemptions');
            $table->boolean('requires_shipping');
            $table->boolean('physical_order');
            $table->boolean('is_premium');
            $table->boolean('use_tango_api');
            $table->integer('toa_id');
            $table->smallInteger('status');
            $table->boolean('display_popup');
            $table->boolean('deleted');

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
