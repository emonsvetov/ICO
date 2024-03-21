<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProgramsExtraTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!Schema::hasTable('programs_extra')) {
            Schema::create('programs_extra', function (Blueprint $table) {
                $table->id();
                $table->integer('program_id')->nullable();
                $table->integer('program_account_holder_id')->nullable();
                $table->integer('factor_valuation')->nullable();
                $table->decimal('points_over_budget', 9, 4)->nullable();
                $table->integer('bill_direct')->nullable();
                $table->tinyInteger('allow_creditcard_deposits')->nullable();
                $table->float('reserve_percentage')->nullable();
                $table->float('setup_fee')->nullable();
                $table->float('discount_rebate_percentage')->nullable();
                $table->float('expiration_rebate_percentage')->nullable();
                $table->float('convenience_fee')->nullable();
                $table->float('percent_total_spend_rebate')->nullable();
                $table->float('budget_number')->nullable();
                $table->float('alarm_percentage')->nullable();
                $table->float('administrative_fee')->nullable();
                $table->double('administrative_fee_factor', 9, 4)->nullable();
                $table->string('administrative_fee_calculation', 24)->nullable();
                $table->float('deposit_fee')->nullable();
                $table->float('fixed_fee')->nullable();
                $table->float('monthly_usage_fee')->nullable();
                $table->float('monthly_recurring_points_billing_percentage')->nullable();
                $table->mediumText('bcc_email_list')->nullable();
                $table->mediumText('cc_email_list')->nullable();
                $table->mediumText('accounts_receivable_email')->nullable();
                $table->tinyInteger('allow_multiple_participants_per_unit')->nullable();
                $table->tinyInteger('uses_units')->nullable();
                $table->tinyInteger('allow_awarding_pending_activation_participants')->nullable();
                $table->integer('default_domain_access_key')->nullable();
                $table->integer('allow_hierarchy_to_view_social_wall')->nullable();
                $table->integer('can_view_hierarchy_social_wall')->nullable();
                $table->integer('allow_managers_to_change_email')->nullable();
                $table->integer('allow_participants_to_change_email')->nullable();
                $table->tinyInteger('air_show_programs_tab')->nullable();
                $table->tinyInteger('air_show_manager_award_tab')->nullable();
                $table->tinyInteger('air_premium_cost_to_program')->nullable();
                $table->tinyInteger('air_show_all_event_list')->nullable();
                $table->tinyInteger('sub_program_groups')->nullable();
                $table->tinyInteger('show_internal_store')->nullable();
                $table->string('rank_range', 3)->nullable();
                $table->integer('approve_grade_id')->nullable();
                $table->string('approve_grade_ids', 40)->nullable();
                $table->string('approve_grade_notification_ids', 40)->nullable();
                $table->mediumText('notification_email_list')->nullable();
                $table->timestamps();

                $table->index(['program_id', 'program_account_holder_id']);
            });
        }

    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('programs_extra');
    }
}
