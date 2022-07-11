<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProgramTemplatesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('program_templates', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('program_id');
            $table->string('small_logo', 100)->nullable();
            $table->string('big_logo', 100)->nullable();
            $table->mediumText('welcome_message', 100)->nullable();
            $table->boolean('is_active')->default(0);
            $table->integer('update_id')->nullable();
            $table->integer('updated_by')->nullable();
            $table->timestamps();
            $table->foreign('program_id')->references('id')->on('programs')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('program_templates');
    }
}
