<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UserGoals extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_goals', function (Blueprint $table) {
            $table->id();
            $table->integer('user_id');
            $table->integer('goal_plan_id');
            $table->integer('next_user_goal_id')->nullable();
            $table->integer('previous_user_goal_id')->nullable();
            $table->decimal('target_value',11,4);
            $table->integer('achieved_callback_id')->nullable();
            $table->integer('exceeded_callback_id')->nullable();
            $table->timestamp('date_met')->nullable();
            $table->timestamp('date_exceeded')->nullable();
            $table->decimal('factor_before', 9,4)->nullable(); //only for sales goal plan type
            $table->decimal('factor_after', 9,4)->nullable();  //only for sales goal plan type
            $table->float('calc_progress_total')->nullable();
            $table->float('calc_progress_percentage')->nullable();
            $table->integer('created_by');
            $table->integer('modified_by')->nullable();
            $table->timestamp('expired')->nullable();
            $table->timestamp('deleted')->nullable();
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
        //
    }
}
