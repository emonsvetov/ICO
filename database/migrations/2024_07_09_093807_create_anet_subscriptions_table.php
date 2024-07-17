<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAnetSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('anet_subscriptions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id')->nullable();
            $table->unsignedBigInteger('program_id')->nullable();

            $table->unsignedBigInteger('subscription_id');
            $table->double('amount');
            $table->tinyInteger('charge_interval_in_months');
            $table->date('subscription_first_charge_date');
            $table->dateTime('subscription_next_charge_date');
            $table->tinyInteger('is_active')->default(0);
            
            $table->dateTime('cancelled')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'program_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('anet_subscriptions');
    }
}
