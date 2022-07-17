<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateGoalPlansTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('goal_plans', function (Blueprint $table) {
           $table->id();
           // $table->bigIncrements('id');
            $table->string('name', 45);
            $table->integer('goal_plan_type_id');
            $table->boolean('automatic_progress'); // If yes then show automatic_frequency and  automatic_value
            $table->string('automatic_frequency',45)->nullable(); // Daily,Weekly,Monthly,Annually //show if automatic_progress is true
            $table->integer('automatic_value')->nullable(); //show if automatic_progress is true
            $table->date('start_date');
            $table->decimal('default_target',11,4);
            $table->string('goal_measurement_label', 45);
            $table->integer('expiration_rule_id'); // in separate table expiration_rules - 12 Months, 1 of Month, End of Next Year,Custom,Annual,Specified,2 Years(7 total)
            $table->integer('annual_expire_month'); //if annual seleced in goal_plan_expiration_dropdown
            $table->integer('annual_expire_day'); //if annual seleced in goal_plan_expiration_dropdown
            $table->integer('custom_expire_offset'); //if custom seleced in goal_plan_expiration_dropdown
            $table->string('custom_expire_units', 16);
            $table->integer('achieved_event_template_id');
            $table->integer('exceeded_event_template_id');
            $table->integer('progress_email_template_id'); //not in old db
            $table->boolean('is_recurring')->default(1);
            $table->boolean('award_per_progress')->default(1);
            $table->boolean('award_email_per_progress')->default(0);
            $table->boolean('progress_requires_unique_ref_num')->default(0);
            $table->boolean('assign_goal_all_participants_default')->default(0);
            
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
        Schema::dropIfExists('goal_plans');
    }
}
