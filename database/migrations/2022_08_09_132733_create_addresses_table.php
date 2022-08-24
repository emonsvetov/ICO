<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAddressesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('addresses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('account_holder_id');
            $table->string('address', 45)->nullable();
            $table->string('address_ext', 45)->nullable();
            $table->string('city', 45)->nullable();
            $table->string('zip', 45)->nullable();
            $table->unsignedBigInteger('state_id')->nullable();
            $table->unsignedBigInteger('country_id')->default(223);
            $table->timestamps();
            $table->softDeletes();

            $table->index('account_holder_id');
            $table->index('state_id');
            $table->index('country_id');

            $table->foreign('state_id')->references('id')->on('states')->onDelete('cascade');
            $table->foreign('country_id')->references('id')->on('countries')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('addresses');
    }
}
