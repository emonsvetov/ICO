<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddV2GoalPlanIdToGoalPlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('goal_plans', function (Blueprint $table) {
            $table->integer('v2_goal_plan_id')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('goal_plans', function (Blueprint $table) {
            $table->dropColumn('v2_goal_plan_id');
        });
    }
}
