<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMerchants extends Migration
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
            $table->integer('parent_id')->nullable();
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
            $table->boolean('website_is_redemption_url')->default(0);
            $table->boolean('get_gift_codes_from_root')->default(0);
            $table->boolean('is_default')->default(0);
            $table->boolean('giftcodes_require_pin')->default(0);
            $table->integer('display_rank_by_priority')->default(0);
            $table->integer('display_rank_by_redemptions')->default(0);
            $table->boolean('requires_shipping')->default(0);
            $table->boolean('physical_order')->default(0);
            $table->boolean('is_premium')->default(0);
            $table->boolean('use_tango_api')->default(0);
            $table->integer('toa_id')->nullable();
            $table->smallInteger('status')->default(0);
            $table->boolean('display_popup')->default(0);

            $table->softDeletes();

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
        Schema::dropIfExists('merchants');
    }
}
