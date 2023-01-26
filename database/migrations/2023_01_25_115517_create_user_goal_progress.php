<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserGoalProgress extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_goal_progress', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('goal_plan_id');
            $table->unsignedBigInteger('user_goal_id');
            $table->unsignedBigInteger('state_type_id');
            $table->decimal('progress_value', 11, 4);
            $table->decimal('target_value', 11, 4);
            $table->mediumText('comment')->nullable();
            $table->unsignedInteger('iteration')->default(0);
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
        Schema::dropIfExists('user_goal_progress');
    }
}
