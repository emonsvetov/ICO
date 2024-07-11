<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateApprovalRelationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('approval_relations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('program_approval_id');
            $table->unsignedBigInteger('awarder_position_id');
            $table->unsignedBigInteger('approver_position_id');
            $table->integer('created_by')->nullable();
            $table->boolean('notifications_enabled')->default(1);
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
        Schema::dropIfExists('approval_relations');
    }
}
