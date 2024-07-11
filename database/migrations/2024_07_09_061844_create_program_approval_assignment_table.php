<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProgramApprovalAssignmentTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('program_approval_assignment', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('program_approval_id')->nullable();
            $table->unsignedBigInteger('position_level_id')->nullable();
            $table->boolean('status')->default(1);
            $table->boolean('notifications_enabled')->default(1);
            $table->integer('created_by')->nullable();
            $table->timestamps();
            $table->index(['id','program_approval_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('program_approval_assignment');
    }
}
