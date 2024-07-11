<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProgramApprovalsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('program_approvals', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('program_id')->nullable();
            $table->unsignedBigInteger('program_parent_id')->nullable();
            $table->smallInteger('step')->unsigned()->nullable()->default(null);
            $table->boolean('status')->default(1);
            $table->integer('created_by')->nullable();
            $table->timestamps();
            $table->index(['id','program_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('program_approvals');
    }
}
