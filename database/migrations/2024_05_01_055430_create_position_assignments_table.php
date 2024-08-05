<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePositionAssignmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('position_assignments', function (Blueprint $table) {
            $table->id();
			$table->unsignedBigInteger('position_level_id');
			$table->unsignedBigInteger('program_id');
			$table->unsignedBigInteger('user_id');
			$table->boolean('status')->default(1);
            $table->timestamps();
			$table->index(['id','position_level_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('position_assignments');
    }
}
