<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProgramBudgetTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('months', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->timestamps();
        });

        Schema::create('program_budget', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('program_id');
            $table->float('budget');
            $table->unsignedBigInteger('month_id');
            $table->integer('year');
            $table->tinyInteger('is_notified');
            $table->foreign('program_id')->references('id')->on('programs')->onDelete('cascade');
            $table->foreign('month_id')->references('id')->on('months')->onDelete('cascade');
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
        Schema::dropIfExists('months');
        Schema::dropIfExists('program_budget');
    }
}
