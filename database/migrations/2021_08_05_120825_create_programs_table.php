<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProgramsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('program_id')->nullable();

            $table->string('name');
            $table->double('setup_fee', 10, 2);
            $table->boolean('is_pay_in_advance')->nullable();
            $table->boolean('is_invoice_for_rewards')->nullable();
            $table->boolean('is_add_default_merchants')->nullable();

            $table->timestamps();

            $table->index(['organization_id','program_id']);

            $table->foreign('program_id')->references('id')->on('programs');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('programs');
    }
}
