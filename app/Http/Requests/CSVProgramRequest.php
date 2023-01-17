<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CSVProgramRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    public function importRules()
    {
        return [
            'external_id'   => 'mustExistInModel:Program|matchWith:external_id|use:external_id|filter:organization_id,=,organization_id',
            // 'program_name'  => 'mustExistInModel:Program|matchWith:name|use:name|filter:organization_id,=,organization_id',
            'program_id'    => 'mustComeFromModel:Program|matchWith:name|use:id|filterConstant:organization_id,=,organization_id',
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'external_id'   => 'nullable|string',
            // 'program_name'  => 'required|string',
            'program_id'    => 'required|integer',
/*
            'program_id'            => 'nullable|integer',

            'type'                  => 'required|string',
            'status'                => 'nullable|string',
            'setup_fee'             => 'required|numeric',
            'factor_valuation'=>'required|integer',
            'is_pay_in_advance'     => 'required|boolean',
            'invoice_for_awards'=> 'required|boolean',
            'is_add_default_merchants'=> 'required|boolean',
			//new
            'public_contact_email'=>'nullable|string|email',
            'prefix'=>'nullable|string',

            //'program_type_id'=>'nullable|integer', pending dropdown
            'corporate_entity'=> 'nullable|string',
            //Expiration Rule:
            'expiration_rule_id'=>'nullable|integer',
            'custom_expire_offset'=>'nullable|integer',
            'custom_expire_units'=>'nullable|string',
            'annual_expire_month'=>'nullable|integer',
            'annual_expire_day'=>'nullable|integer',
            //Awarding Settings
            'allow_awarding_pending_activation_participants'=>'nullable|boolean',
            //Units Settings
            'uses_units'=>'nullable|boolean',
            'allow_multiple_participants_per_unit'=>'nullable|boolean',
            //Expiration Notice Settings
            'send_points_expire_notices'=>'nullable|boolean',
            'points_expire_notice_days'=>'nullable|integer',
            //Program Settings
            'allow_managers_to_change_email'=>'nullable|boolean',
            'allow_participants_to_change_email'=>'nullable|boolean',
            //Air with Core Settings
            //Pending- Allocate cost to program for premium merchants: checkbox
            //Sub Program Groups
            'sub_program_groups'=>'nullable|boolean',
            //Set Event Limits
            'events_has_limits'=>'nullable|boolean',
            'event_has_category'=>'nullable|boolean',
            //Event Configurations
            'show_internal_store'=>'nullable|boolean',
            'has_promotional_award'=>'nullable|boolean',
            //Use One leaderboard
            'use_one_leaderboard'=>'nullable|boolean',
            //Approvals and Budget
            'use_cascading_approvals'=>'nullable|boolean','enable_schedule_awards'=>'nullable|boolean',
            'use_budget_cascading'=>'nullable|boolean',
            'budget_summary'=>'nullable|boolean',
            //Reference Document
            'enable_reference_documents'=>'nullable|boolean',
            //Dashboard Reports
            'consolidated_dashboard_reports'=>'nullable|boolean',
            //Global Search
            'enable_global_search'=>'nullable|boolean',
            //Archive
            'archive_program'=>'nullable|boolean',
            //Deactivate Account
            'deactivate_account'=>'nullable|boolean',
            //Billing  Information
            'is_pay_in_advance'=>'nullable|boolean',
            'invoice_for_awards'=>'nullable|boolean',
            'create_invoices'=>'nullable|boolean',
            'allow_creditcard_deposits'=>'nullable|boolean',
			//Transaction Fees - This is many to one with a add tier amount button that adds new fields (Pending) Tier amount : text, Transaction fee: text
            'reserve_percentage'=>'nullable|integer',
            'discount_rebate_percentage'=>'nullable|integer',
            'expiration_rebate_percentage'=>'nullable|integer',
            'percent_total_spend_rebate'=>'nullable|integer',
            'bill_parent_program'=>'nullable|boolean',
            'administrative_fee'=>'nullable|integer',
            'administrative_fee_factor'=>'nullable|integer',
			//	Calculation: dropdown Pending
            'deposit_fee'=>'nullable|integer',
            'fixed_fee'=>'nullable|integer',
            'convenience_fee'=>'nullable|integer',
            'monthly_usage_fee'=>'nullable|integer',
            'accounts_receivable_email'=>'nullable|string|email',
            'bcc_email_list'=>'nullable|string',
            'cc_email_list'=>'nullable|string',
            'notification_email_list'=>'nullable|string',
            //Address Information
			//'state_id'=> 'nullable|integer', pending dropdown
			'address'=>'nullable|string',
			'address_ext'=>'nullable|string',
			'city'=>'nullable|string',
            'state'=>'nullable|string',
			'zip'=>'nullable|string',
            //Social Wall
            'allow_hierarchy_to_view_social_wall'=>'nullable|boolean',
            'can_post_social_wall_comments'=>'nullable|boolean',
            'can_view_hierarchy_social_wall'=>'nullable|boolean',
            'managers_can_post_social_wall_messages'=>'nullable|boolean',
            'share_siblings_social_wall'=>'nullable|boolean',
            'show_all_social_wall'=>'nullable|boolean',
            'social_wall_separation'=>'nullable|boolean',
            'uses_social_wall'=>'nullable|boolean',
            'amount_override_limit_percent'=>'nullable|integer',
            'awards_limit_amount_override'=>'nullable|boolean',
			//Brochures
            'brochures_enable_on_participant'=>'nullable|boolean',
			//CRM Settings
			'crm_company_tag_id'=>'nullable|integer',
			'crm_reminder_email_delay_1'=>'nullable|integer',
			'crm_reminder_email_delay_2'=>'nullable|integer',
			'crm_reminder_email_delay_3'=>'nullable|integer',
			'crm_reminder_email_delay_4'=>'nullable|integer',
            //CSV Importer Options
            'csv_import_option_use_external_program_id'=>'nullable|boolean',
            'csv_import_option_use_organization_uid'=>'nullable|boolean',
            //Merchant Options
            'google_custom_search_engine_cx'=>'nullable|string',
            //Invoice Settings
            'invoice_po_number'=>'nullable|string',
            //Leaderboards
            'leaderboard_seperation'=>'nullable|boolean',
            'share_siblings_leader_board'=>'nullable|boolean',
            'uses_leaderboards'=>'nullable|boolean',
            //Manager Settings
            'manager_can_award_all_program_participants'=>'nullable|boolean',
            'program_managers_can_invite_participants'=>'nullable|boolean',
            //Peer 2 Peer
            'peer_award_seperation'=>'nullable|boolean',
            'peer_search_seperation'=>'nullable|boolean',
            'share_siblings_peer2peer'=>'nullable|boolean',
            'uses_hierarchy_peer2peer'=>'nullable|boolean',
            'uses_peer2peer'=>'nullable|boolean',
            //Points Ratio
            'point_ratio_seperation'=>'nullable|integer',
            //Team
            'team_management_view'=>'nullable|boolean',
            //Goal Tracker
            'uses_goal_tracker'=>'nullable|boolean',
            'country'=>'nullable|string',
            'transaction_fee'=>'nullable|numeric',*/
        ];
    }
}
