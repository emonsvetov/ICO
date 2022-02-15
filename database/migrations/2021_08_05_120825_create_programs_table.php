<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProgramsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('programs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('organization_id');
            $table->unsignedBigInteger('program_id')->nullable();

            $table->string('name');
            $table->double('setup_fee', 10, 2);
            $table->boolean('is_pay_in_advance')->nullable();
            $table->boolean('is_invoice_for_rewards')->nullable();
            $table->boolean('is_add_default_merchants')->nullable();


            $table->index(['organization_id','program_id']);

            $table->foreign('program_id')->references('id')->on('programs');

			//new
            $table->string('public_contact_email',165)->nullable();
            $table->string('prefix',8)->nullable();
            $table->string('external_id',45)->nullable();
			//Label  - don�t use 
            //$table->integer('program_type_id')->nullable(); //pending dropdown
            $table->string('corporate_entity', 250)->nullable();
            //Expiration Rule:
            //$table->tinyInteger('expiration_rule_id')->default('1');
            $table->boolean('expiration_rule_id')->default('1');
            $table->integer('custom_expire_offset')->nullable(); 
            $table->string('custom_expire_units',16)->nullable();
            $table->integer('annual_expire_month')->nullable();
            $table->integer('annual_expire_day')->nullable();
            //Awarding Settings
            $table->boolean('allow_awarding_pending_activation_participants')->nullable();
            //Units Settings
            $table->boolean('uses_units')->nullable();
            $table->boolean('allow_multiple_participants_per_unit')->nullable();
            //Expiration Notice Settings
            $table->boolean('send_points_expire_notices')->nullable(); //radio
            $table->integer('points_expire_notice_days')->nullable()->default('14');//ques
            //Program Settings
            $table->boolean('allow_managers_to_change_email')->nullable();
            $table->boolean('allow_participants_to_change_email')->nullable();
            //Air with Core Settings
            //Pending- Allocate cost to program for premium merchants: checkbox
            //Sub Program Groups
            $table->boolean('sub_program_groups')->nullable();
            //Set Event Limits
            $table->boolean('events_has_limits')->nullable();
            $table->boolean('event_has_category')->nullable();
            //Event Configurations
            $table->boolean('show_internal_store')->nullable();
            $table->boolean('has_promotional_award')->nullable();
            //Use One leaderboard
            $table->boolean('use_one_leaderboard')->nullable();
            //Approvals and Budget
            $table->boolean('use_cascading_approvals')->nullable();
            $table->boolean('enable_schedule_awards')->nullable();
            $table->boolean('use_budget_cascading')->nullable();
            $table->boolean('budget_summary')->nullable();
            //Reference Document
            $table->boolean('enable_reference_documents')->nullable();
            //Dashboard Reports
            $table->boolean('consolidated_dashboard_reports')->nullable();
            //Global Search
            $table->boolean('enable_global_search')->nullable();
            //Archive
            $table->boolean('archive_program')->nullable();
            //Deactivate Account 
            $table->boolean('deactivate_account')->nullable();
            //Billing  Information
            //$table->boolean('pay_in_advance')->nullable();
           //$table->boolean('invoice_for_awards')->nullable();
            $table->boolean('create_invoices')->nullable();
            $table->boolean('allow_creditcard_deposits')->nullable();
			//Transaction Fees - This is many to one with a add tier amount button that adds new fields (Pending) Tier amount : text, Transaction fee: text
            $table->float('reserve_percentage')->nullable(); 
            $table->float('discount_rebate_percentage')->nullable(); 
            $table->float('expiration_rebate_percentage')->nullable(); 
            $table->float('percent_total_spend_rebate')->nullable();
            $table->boolean('bill_parent_program')->nullable();  
            $table->float('administrative_fee')->nullable(); 
			//	Calculation: dropdown Pending
            $table->double('administrative_fee_factor',9, 4)->nullable(); 
            $table->float('deposit_fee',8, 2)->nullable(); 
            $table->float('fixed_fee')->nullable(); 
            $table->float('convenience_fee')->nullable();
            $table->float('monthly_usage_fee')->nullable();  
            $table->integer('factor_valuation')->nullable(); 

            $table->mediumText('accounts_receivable_email',165)->nullable();
            $table->mediumText('bcc_email_list')->nullable();
            $table->mediumText('cc_email_list')->nullable();
            $table->mediumText('notification_email_list')->nullable();
			//Address Information
			//$table->unsignedBigInteger('state_id')->nullable(); //pending dropdown
			$table->string('address',45)->nullable(); 
			$table->string('address_ext',45)->nullable(); 
			$table->string('city',45)->nullable(); 
			$table->string('zip',45)->nullable(); 
            //Social Wall
            $table->boolean('allow_hierarchy_to_view_social_wall')->nullable();
            $table->boolean('can_post_social_wall_comments')->nullable();
            $table->boolean('can_view_hierarchy_social_wall')->nullable();
            $table->boolean('managers_can_post_social_wall_messages')->nullable();
            $table->boolean('share_siblings_social_wall')->nullable();
            $table->boolean('show_all_social_wall')->nullable();
            $table->boolean('social_wall_separation')->nullable();
            $table->boolean('uses_social_wall')->nullable();
            //Awarding Settings
            $table->integer('amount_override_limit_percent')->nullable();
            $table->boolean('awards_limit_amount_override')->nullable();
            //Brochures
            $table->boolean('brochures_enable_on_participant')->nullable();
			//CRM Settings
			$table->integer('crm_company_tag_id')->nullable();
			$table->integer('crm_reminder_email_delay_1')->nullable();
			$table->integer('crm_reminder_email_delay_2')->nullable();
			$table->integer('crm_reminder_email_delay_3')->nullable();
			$table->integer('crm_reminder_email_delay_4')->nullable();
            //CSV Importer Options 
            $table->boolean('csv_import_option_use_external_program_id')->nullable();
            $table->boolean('csv_import_option_use_organization_uid')->nullable();
            //Merchant Options
            $table->string('google_custom_search_engine_cx', 250)->nullable();
            //Invoice Settings
            $table->string('invoice_po_number', 250)->nullable();
            //Leaderboards
            $table->boolean('leaderboard_seperation')->nullable();
            $table->boolean('share_siblings_leader_board')->nullable();
            $table->boolean('uses_leaderbaords')->nullable();
            //Manager Settings
            $table->boolean('manager_can_award_all_program_participants')->nullable();
            $table->boolean('program_managers_can_invite_participants')->nullable();

            //Peer 2 Peer
            $table->boolean('peer_award_seperation')->nullable();
            $table->boolean('peer_search_seperation')->nullable();
            $table->boolean('share_siblings_peer2peer')->nullable();
            $table->boolean('uses_hierarchy_peer2peer')->nullable();
            $table->boolean('uses_peer2peer')->nullable();
            //Points Ratio
            $table->integer('point_ratio_seperation')->nullable();
            //Team
            $table->boolean('team_management_view')->nullable();
            //Goal Tracker
            $table->boolean('uses_goal_tracker')->nullable();

			$table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('programs');
        Schema::table('programs', function (Blueprint $table) {
            $table->dropSoftDeletes();
        });
    }
}
