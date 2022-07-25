<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLeaderboardGoalPlanTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('leaderboard_goal_plan', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('leaderboard_id');
            $table->unsignedBigInteger('goal_plan_id');
            $table->timestamps();

            $table->index('leaderboard_id');
            $table->index('goal_plan_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('leaderboard_goal_plan');
    }
}
