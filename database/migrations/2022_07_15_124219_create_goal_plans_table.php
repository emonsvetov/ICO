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
            $table->integer('next_goal_id')->nullable();
            $table->integer('previous_goal_id')->nullable();
            $table->unsignedBigInteger('program_id');
            $table->unsignedBigInteger('organization_id');
            $table->string('name', 45);
            $table->string('goal_measurement_label', 45);
            $table->integer('goal_plan_type_id');
            $table->integer('state_type_id');
            $table->decimal('default_target',11,4);
            $table->integer('email_template_id')->nullable();
            $table->mediumText('notification_body')->nullable();
            $table->mediumText('achieved_callback_id')->nullable();
            $table->mediumText('exceeded_callback_id')->nullable();
            $table->integer('achieved_event_id')->nullable(); //achieved_event_template_id 
            $table->integer('exceeded_event_id')->nullable(); //exceeded_event_template_id  only for sales goal plan type
            $table->boolean('automatic_progress'); // If yes then show automatic_frequency and  automatic_value
            $table->string('automatic_frequency',8)->nullable(); // Daily,Weekly,Monthly,Annually //show if automatic_progress is true
            $table->integer('automatic_value')->nullable(); //show if automatic_progress is true
            $table->integer('expiration_rule_id'); // in separate table expiration_rules - 12 Months, 1 of Month, End of Next Year,Custom,Annual,Specified,2 Years(7 total)
            $table->integer('custom_expire_offset')->nullable(); //if custom seleced in goal_plan_expiration_dropdown
            $table->string('custom_expire_units', 16)->nullable();
            $table->integer('annual_expire_month')->nullable(); //if annual seleced in goal_plan_expiration_dropdown
            $table->integer('annual_expire_day')->nullable(); //if annual seleced in goal_plan_expiration_dropdown
            $table->date('date_begin'); //start date
            $table->date('date_end'); //expire date
            $table->decimal('factor_before', 9,4)->nullable(); //only for sales goal plan type
            $table->decimal('factor_after', 9,4)->nullable();  //only for sales goal plan type
            $table->boolean('is_recurring')->default(1);
            $table->boolean('award_per_progress')->default(1);
            $table->boolean('award_email_per_progress')->default(0);
            $table->boolean('progress_requires_unique_ref_num')->default(0);
            $table->integer('progress_notification_email_id');
            $table->boolean('assign_goal_all_participants_default')->nullable()->default(0);
            $table->integer('created_by');
            $table->integer('modified_by')->nullable();
            $table->timestamp('expired')->nullable();
            //$table->timestamp('deleted')->nullable();
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
