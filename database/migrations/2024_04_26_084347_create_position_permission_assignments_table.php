<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePositionPermissionAssignmentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('position_permission_assignments', function (Blueprint $table) {
            $table->id();
			$table->unsignedBigInteger('position_level_id');
			$table->unsignedBigInteger('position_permission_id');
            $table->timestamps();
			$table->softDeletes();
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
        Schema::dropIfExists('position_permission_assignments');
    }
}
