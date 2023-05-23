<?php

namespace App\Services\v2migrate\Trait;

use Exception;
use App\Models\Address;
use App\Models\Program;
use App\Models\Domain;
use App\Models\DomainIP;
use App\Models\Event;
use App\Models\Leaderboard;
use stdClass;

trait CreateProgramTrait
{
    public $newPrograms = [];
    public function createProgram($v3_organization_id, $v2Program, $parent_id = null)
    {
        if( empty($v3_organization_id) || empty($v2Program->account_holder_id) ) {
            throw new Exception( "Required fields in v2 table to sync properly are missing. Termininating!");
            exit;
        }

        //Check for existence

        $exists = Program::where("v2_account_holder_id", $v2Program->account_holder_id)->exists();

        if( $exists ) {
            if( !$this->overwriteProgram ) {
                return "V2Program {$v2Program->account_holder_id} already exists and not set to update";
            }
        }

        /****
            * $program
            * v2 programs fields
            program_type_id                     int                                 not null,
            revenue_share                       decimal(9, 4)       default 0.5000  not null comment ' column is being dropped',
            revenue_share_expire                decimal(9, 4)       default 0.0000  not null comment ' column is being dropped',
            user_deactivation                   int                 default 1209600 not null comment 'Custom time in seconds before a user in pending deactivation is actually deactivated',
            label                               varchar(45)                         null,
            default_contact_account_holder_id   int                 default 252711  null,
            program_state_id                    int                 default 1       null,
            infusionsoft_settings_id            int                                 null,
            premium_fees_billed_to_program      tinyint(1)          default 0       null,
            self_enrollment_token               varchar(50)                         null,
            award_limit_period                  varchar(10)                         null,
            max_awards_made_for_period          int                                 null,
            max_awards_receive_for_period       int                                 null,
            display_external_id                 tinyint             default 0       null,
            deactivate                          tinyint(2)          default 0       null,
            has_category                        smallint(1)         default 0       null,
            allow_same_step_approval            enum('1', '0')     default '0'     not null,
            award_credit_enabled                enum('1', '0')     default '0'     not null,
            deactivation_date                   datetime                            null,
            confirmation_note                   varchar(250)                        null,
            technical_reason_id                 int                                 null,
            updated_by                          int                                 null,
            deactivation_email_template_id      int                                 null,
            program_is_demo                     tinyint(1)          default 0       not null,
            program_demo_changed_date           int(11) unsigned                    not null,
            program_demo_changed_by             int(11) unsigned                    not null,
            update_id                           int(11) unsigned                    null,
            balance_threshold                   int(11) unsigned                    null,
            send_balance_threshold_notification enum('1', '0')     default '0'     not null,
            silent_deactivate                   tinyint(1) unsigned default 0       null,
            low_balance_email                   varchar(255)                        null,
            award_credit_x_days                 int(11) unsigned                    not null,
            award_credit_date_start             datetime                            null,
         */

        /****
            * $extra_info
            [program_account_holder_id] => 203604
            [points_over_budget] => 20
            [bill_direct] => 1
            [budget_number] => 0
            [alarm_percentage] => 0
            [monthly_recurring_points_billing_percentage] => 0
            [default_domain_access_key] => 6
            [air_show_programs_tab] => 1
            [air_show_manager_award_tab] => 1
            [air_premium_cost_to_program] =>
            [air_show_all_event_list] => 1
            [sub_program_groups] => 0
            [show_internal_store] => 0
            [rank_range] =>
            [approve_grade_id] => 0
            [approve_grade_ids] =>
            [approve_grade_notification_ids] =>                                                                                  srice@yescommunities.com
            [default_domain_name] => employeeyesrewards.incentco.com
        */
        /****
             *
             * $program_config_fields
            [allow_cross_hierarchy_display_filtering] => 1
            [allow_hierarchy_to_view_social_wall] =>
            [can_view_hierarchy_social_wall] =>
            [csv_importer_add_active_participants_no_email] => csv_importer_add_active_participants_no_email
            [csv_importer_add_and_award_participants] => csv_importer_add_and_award_participants
            [csv_importer_add_and_award_participants_with_event] => csv_importer_add_and_award_participants_with_event
            [csv_importer_add_events] => csv_importer_add_events
            [csv_importer_add_goal_progress] => csv_importer_add_goal_progress
            [csv_importer_add_goals_to_participants] => csv_importer_add_goals_to_participants
            [csv_importer_add_managers] => csv_importer_add_managers
            [csv_importer_add_new_participants] => csv_importer_add_new_participants
            [csv_importer_add_participant_redemptions] => csv_importer_add_participant_redemptions
            [csv_importer_add_participants] => csv_importer_update_employer_yes
            [csv_importer_award_participants] => csv_importer_award_participants
            [csv_importer_custom_file] => csv_importer_custom_file_yes_duplicates
            [csv_importer_deactivate_participants] => csv_importer_deactivate_participants
            [csv_importer_peer_to_peer_awards] => csv_importer_peer_to_peer_awards
            [csv_importer_update_employer_yes] => csv_importer_update_employer_yes
            [csv_importer_update_participants] => csv_importer_update_participants
            [display_brochures_across_hierarchy] =>
            [mobile_app_management] =>
            [referral_notification_recipient_management] =>
            [self_enrollment_enable] =>
            [social_wall_remove_social] =>
            [social_wall_seperation] => 1
            [uses_leaderbaords] =>
        )
         */

        if( !property_exists($v2Program, 'name') || !property_exists($v2Program, 'program_type')) {
            //We can assume that we need to pull "program_info"
            //But before that we need to preserve "sub_programs" if we are in recursive loop, since we already have fetched $sub_programs tree;
            $sub_programs_exists = property_exists($v2Program, 'sub_programs') ? true : false;
            $sub_programs = $sub_programs_exists ? $v2Program->sub_programs: null;

            //Get program info
            $v2Program = $this->get_program_info ( $v2Program->account_holder_id );

            //if sub program property existed put it back
            if( $sub_programs_exists ) {
                $v2Program->sub_programs = $sub_programs;
            }
        }

        // pr('$v2Program');
        // pr($v2Program);

        if( !$v2Program ) {
            throw new Exception( "Invalid program passed to createProgram function, terminating!");
            exit;
        }

        $program_config_fields_grouped = $this->read_program_config_fields_by_name ( $v2Program->account_holder_id );
        $program_config_fields = [];
        foreach($program_config_fields_grouped as $program_config_field)    {
            $program_config_fields[$program_config_field->name] = $program_config_field->value;
        }

        $extra_info = $this->read_extra_program_info ( ( int )$v2Program->account_holder_id );

        $data = [
            // 'account_holder_id'                              => (int)$v2Program->account_holder_id + $max_account_holder_id + 10000,
            // 'organization_id'                                => $v2Program->organization_id,
            'parent_id'                                      => $parent_id,
            'name'                                           => $v2Program->name,
            'type'                                           => 'default',
            'status_id'                                      => (int)$v2Program->program_state_id,
            'setup_fee'                                      => $extra_info->setup_fee,
            'is_pay_in_advance'                              => 1,
            'invoice_for_awards'                             => $v2Program->invoice_for_awards,
            'is_add_default_merchants'                       => 1,
            'public_contact_email'                           => $v2Program->public_contact_email,
            'prefix'                                         => $v2Program->prefix,
            'external_id'                                    => $v2Program->external_id,
            'corporate_entity'                               => $v2Program->corporate_entity,
            'expiration_rule_id'                             => $v2Program->expiration_rule_id ? (int)$v2Program->expiration_rule_id : null,
            'custom_expire_offset'                           => $v2Program->custom_expire_offset ? (int)$v2Program->custom_expire_offset : null,
            'custom_expire_units'                            => $v2Program->custom_expire_units,
            'annual_expire_month'                            => $v2Program->annual_expire_month ? (int)$v2Program->annual_expire_month : null,
            'annual_expire_day'                              => $v2Program->annual_expire_day ? (int)$v2Program->annual_expire_day : null,
            'allow_awarding_pending_activation_participants' => $extra_info->allow_awarding_pending_activation_participants,
            'uses_units'                                     => $extra_info->uses_units,
            'allow_multiple_participants_per_unit'           => $extra_info->allow_multiple_participants_per_unit,
            'send_points_expire_notices'                     => $v2Program->send_points_expire_notices,
            'points_expire_notice_days'                      => $v2Program->points_expire_notice_days ? (int)$v2Program->points_expire_notice_days : null,
            'allow_managers_to_change_email'                 => $extra_info->allow_managers_to_change_email,
            'allow_participants_to_change_email'             => $extra_info->allow_participants_to_change_email,
            'sub_program_groups'                             => $extra_info->sub_program_groups,
            'events_has_limits'                              => $v2Program->events_has_limits,
            'event_has_category'                             => $v2Program->has_category,
            'show_internal_store'                            => $extra_info->show_internal_store,
            'has_promotional_award'                          => $v2Program->has_promotional_award,
            'use_one_leaderboard'                            => $v2Program->use_one_leaderboard,
            'use_cascading_approvals'                        => (int)$v2Program->use_cascading_approvals,
            'enable_schedule_awards'                         => $v2Program->enable_schedule_awards,
            'use_budget_cascading'                           => (int)$v2Program->use_budget_cascading,
            'budget_summary'                                 => (int)$v2Program->budget_summary,
            'enable_reference_documents'                     => (int)$v2Program->enable_reference_documents,
            'consolidated_dashboard_reports'                 => $v2Program->consolidated_dashboard_reports,
            'enable_global_search'                           => $v2Program->enable_global_search,
            'archive_program'                                => null,
            'deactivate_account'                             => null,
            'create_invoices'                                => $v2Program->create_invoices,
            'allow_creditcard_deposits'                      => $extra_info->allow_creditcard_deposits,
            'reserve_percentage'                             => $extra_info->reserve_percentage ? (int)$extra_info->reserve_percentage : null,
            'discount_rebate_percentage'                     => $extra_info->discount_rebate_percentage ? (int)$extra_info->discount_rebate_percentage : null,
            'expiration_rebate_percentage'                   => $extra_info->expiration_rebate_percentage ? (int)$extra_info->expiration_rebate_percentage : null,
            'percent_total_spend_rebate'                     => $extra_info->percent_total_spend_rebate ? (int)$extra_info->percent_total_spend_rebate : null,
            'bill_parent_program'                            => null,
            'administrative_fee'                             => $extra_info->administrative_fee ? (int)$extra_info->administrative_fee : null,
            'administrative_fee_factor'                      => $extra_info->administrative_fee_factor ? (int)$extra_info->administrative_fee_factor : null,
            'administrative_fee_calculation'                 => $extra_info->administrative_fee_calculation ? $extra_info->administrative_fee_calculation : 'participants',
            'transaction_fee'                                => null,
            'deposit_fee'                                    => $extra_info->deposit_fee ? (int)$extra_info->deposit_fee : null,
            'fixed_fee'                                      => $extra_info->fixed_fee ? (int)$extra_info->fixed_fee : null,
            'convenience_fee'                                => $extra_info->convenience_fee ? (int)$extra_info->convenience_fee : null,
            'monthly_usage_fee'                              => $extra_info->monthly_usage_fee ? (int)$extra_info->monthly_usage_fee : null,
            'factor_valuation'                               => (int)$extra_info->factor_valuation,
            'accounts_receivable_email'                      => $extra_info->accounts_receivable_email,
            'bcc_email_list'                                 => trim($extra_info->bcc_email_list),
            'cc_email_list'                                  => trim($extra_info->cc_email_list),
            'notification_email_list'                        => trim($extra_info->notification_email_list),
            'allow_hierarchy_to_view_social_wall'            => $extra_info->allow_hierarchy_to_view_social_wall,
            'can_post_social_wall_comments'                  => $program_config_fields['can_post_social_wall_comments'],
            'can_view_hierarchy_social_wall'                 => $extra_info->can_view_hierarchy_social_wall,
            'managers_can_post_social_wall_messages'         => $program_config_fields['managers_can_post_social_wall_messages'],
            'share_siblings_social_wall'                     => $program_config_fields['share_siblings_social_wall'],
            'show_all_social_wall'                           => $program_config_fields['show_all_social_wall'],
            'social_wall_separation'                         => null,
            'uses_social_wall'                               => $program_config_fields['uses_social_wall'],
            'amount_override_limit_percent'                  => $program_config_fields['amount_override_limit_percent'] ? $program_config_fields['amount_override_limit_percent'] : null,
            'awards_limit_amount_override'                   => $program_config_fields['awards_limit_amount_override'],
            'brochures_enable_on_participant'                => $program_config_fields['brochures_enable_on_participant'],
            'crm_company_tag_id'                             => $program_config_fields['crm_company_tag_id'] ? $program_config_fields['crm_company_tag_id'] : null,
            'crm_reminder_email_delay_1'                     => $program_config_fields['crm_reminder_email_delay_1'] ? $program_config_fields['crm_reminder_email_delay_1'] : null,
            'crm_reminder_email_delay_2'                     => $program_config_fields['crm_reminder_email_delay_2'] ? $program_config_fields['crm_reminder_email_delay_2'] : null,
            'crm_reminder_email_delay_3'                     => $program_config_fields['crm_reminder_email_delay_3'] ? $program_config_fields['crm_reminder_email_delay_3'] : null,
            'crm_reminder_email_delay_4'                     => $program_config_fields['crm_reminder_email_delay_4'] ? $program_config_fields['crm_reminder_email_delay_4'] : null,
            'csv_import_option_use_external_program_id'      => $program_config_fields['csv_import_option_use_external_program_id'],
            'csv_import_option_use_organization_uid'         => $program_config_fields['csv_import_option_use_organization_uid'],
            'google_custom_search_engine_cx'                 => $program_config_fields['google_custom_search_engine_cx'],
            'invoice_po_number'                              => $program_config_fields['invoice_po_number'],
            'leaderboard_seperation'                         => $program_config_fields['leaderboard_seperation'],
            'share_siblings_leader_board'                    => $program_config_fields['share_siblings_leader_board'],
            'uses_leaderboards'                              => null,
            'manager_can_award_all_program_participants'     => $program_config_fields['manager_can_award_all_program_participants'],
            'program_managers_can_invite_participants'       => $program_config_fields['program_managers_can_invite_participants'],
            'peer_award_seperation'                          => $program_config_fields['peer_award_seperation'],
            'peer_search_seperation'                         => $program_config_fields['peer_search_seperation'],
            'share_siblings_peer2peer'                       => $program_config_fields['share_siblings_peer2peer'],
            'uses_hierarchy_peer2peer'                       => $program_config_fields['uses_hierarchy_peer2peer'],
            'uses_peer2peer'                                 => $program_config_fields['uses_peer2peer'],
            'point_ratio_seperation'                         => $program_config_fields['point_ratio_seperation'] ? $program_config_fields['point_ratio_seperation'] : null,
            'team_management_view'                          => $program_config_fields['team_management_view'],
            'uses_goal_tracker'                              => $program_config_fields['uses_goal_tracker'],
            'enable_upload_while_awarding'                   => false,
            'amount_override_percentage'                     => 0,
            'remove_social_from_pending_deactivation'        => false,
            'is_demo'                                        => false,
            'allow_award_peers_not_logged_into'              => false,
            'allow_search_peers_not_logged_into'             => false,
            'allow_view_leaderboards_not_logged_into'        => false,
        ];

        // pr($data);
        // exit;

        try{
            $newProgram = $this->programService->create(
                $data +
                [
                    'organization_id' => $v3_organization_id,
                    'v2_account_holder_id' => $v2Program->account_holder_id,
                ]
            );

            print("New V3 Program created for V2 Program: {$v2Program->account_holder_id}=>{$newProgram->id}\n");

            $this->importedPrograms[] = $newProgram;
            $this->importedProgramsCount++;

            $this->v2db->statement("UPDATE ". PROGRAMS . " SET `v3_organization_id` = {$v3_organization_id}, `v3_program_id` = {$newProgram->id} WHERE `account_holder_id` = {$v2Program->account_holder_id}");

            print("V2 Program updated with v3 identifiying fields v3_organization_id, v3_program_id\n");

            if( !$parent_id ) { //Pull and Assign Domains if it is a root program(?)
                $domains = $this->v2db->select("SELECT d.name, d.access_key, d.secret_key FROM `domains` d JOIN domains_has_programs dhp on dhp.domains_access_key = d.access_key JOIN programs p on p.account_holder_id = dhp.programs_id where p.account_holder_id = {$v2Program->account_holder_id}");

                if( $domains ) {
                    foreach ($domains as $domain) {
                        //Find check
                        print("Finding domain {$domain->name} for Program\n");
                        $v3Domain = Domain::where('name', trim($domain->name))->first();
                        if( !$v3Domain )    {
                            print("Domain {$domain->name} not found, creating\n");
                            $v3Domain = Domain::create([
                                'organization_id' => $v3_organization_id,
                                'name' => $domain->name,
                                'secret_key' => $domain->secret_key,
                                'v2_domain_id' => $domain->access_key
                            ]);
                            $v3Domain->programs()->sync( [$newProgram->id], false);
                            $this->v2db->statement("UPDATE domains SET `v3_domain_id` = {$v3Domain->id} WHERE `access_key` = {$domain->access_key}");
                            print("New Domain {$domain->name} created & synched\n");
                        } else {
                            print("Domain {$domain->name} found\n");
                        }

                        if( $v3Domain ) {
                            //Now get domain IPs
                            $domainIps = $this->v2db->select("SELECT `ip_address` FROM `domains_ips` where domain_access_key = {$domain->access_key}");
                            if( $domainIps ) {
                                foreach($domainIps as $domainIp) {
                                    $v3DomainIp = DomainIP::where('ip_address', $domainIp->ip_address)->where('domain_id', $v3Domain->id)->first();
                                    if( !$v3DomainIp ) {
                                        $newDomainIp = DomainIP::create([
                                            'ip_address' => $domainIp->ip_address,
                                            'domain_id' => $v3Domain->id
                                        ]);
                                        print("Domain IP {$newDomainIp->ip_address} inserted for domain:{$v3Domain->name}\n");
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // Pull addresses

            $addresses = $this->v2db->select("SELECT * FROM `address` WHERE `account_holder_id` = {$v2Program->account_holder_id}");
            if( $addresses ) {
                foreach( $addresses as $address ) {
                    $addressData = [
                        'account_holder_id' => $newProgram->account_holder_id,
                        'address' => $address->address,
                        'address_ext' => $address->address_ext,
                        'city' => $address->city,
                        'state_id' => $address->state_id,
                        'zip' => $address->zip,
                        'country_id' => $address->country_id,
                        'created_at' => now()
                    ];

                    Address::create($addressData);
                    print("Address created for new Program: {$newProgram->id}\n");
                }
            }

            // Pull events

            $v2Events = $this->v2db->select("SELECT * FROM `event_templates` WHERE `program_account_holder_id` = {$v2Program->account_holder_id}");
            if( $v2Events ) {
                foreach( $v2Events as $event ) {
                    $eventData = [
                        'organization_id' => $newProgram->organization_id,
                        'program_id' => $newProgram->id,
                        'name' => $event->name,
                        'event_type_id' => $event->event_type_id,
                        'enable' => ((int)$event->event_state_id) == 13 ? 1 : 0,
                        'amount_override' => $event->amount_override,
                        'award_message_editable' => $event->award_message_editable,
                        'ledger_code' => $event->ledger_code,
                        'amount_override' => $event->amount_override,
                        'post_to_social_wall' => $event->post_to_social_wall,
                        'award_message_editable' => $event->award_message_editable,
                        'is_promotional' => $event->is_promotional,
                        'is_birthday_award' => $event->is_birthday_award,
                        'is_work_anniversary_award' => $event->is_work_anniversary_award,
                        'include_in_budget' => $event->include_in_budget,
                        'enable_schedule_award' => $event->enable_schedule_awards,
                        'message' => $event->notification_body,
                        'initiate_award_to_award' => $event->initiate_award_to_award,
                        'only_internal_redeemable' => $event->only_internal_redeemable,
                        'is_team_award' => $event->is_team_award,
                        'max_awardable_amount' => 0,
                        'v2_event_id' => $event->id,
                    ];

                    $newEvent = Event::create($eventData);
                    $this->v2db->statement("UPDATE `event_templates` SET `v3_event_id` = {$newEvent->id} WHERE `id` = {$event->id}");
                    print("Event:{$newEvent->id} created for new Program: {$newProgram->id}\n");
                }
            }

            $v2Leaderboards = $this->v2db->select("SELECT * FROM `leaderboards` WHERE `program_account_holder_id` = {$v2Program->account_holder_id}");
            if( $v2Leaderboards ) {
                foreach( $v2Leaderboards as $v2Leaderboard ) {
                    //Create leaderboard
                    $leaderboardData = [
                        'organization_id' => $newProgram->organization_id,
                        'program_id' => $newProgram->id,
                        'name' => $v2Leaderboard->name,
                        'status_id' => $v2Leaderboard->state_type_id,
                        'visible' => $v2Leaderboard->visible,
                        'one_leaderboard' => $v2Leaderboard->one_leaderboard,
                        'v2_leaderboard_id' => $v2Leaderboard->id,
                    ];

                    $newLeaderboard = Leaderboard::create($leaderboardData);
                    $this->v2db->statement("UPDATE `leaderboards` SET `v3_leaderboard_id` = {$newLeaderboard->id} WHERE `id` = {$v2Leaderboard->id}");
                    print("Leaderboard:{$newLeaderboard->id} created for program: {$newProgram->id}\n");
                }
            }

            if( !property_exists($v2Program, 'sub_programs') ) { //if root program
                $children_heirarchy_list = $this->read_list_children_heirarchy(( int )$v2Program->account_holder_id);
                // pr("children_heirarchy_list count:");
                // pr(count($children_heirarchy_list));
                $programs_tree = array ();
                if ( $children_heirarchy_list ) {
                    $programs_tree = sort_programs_by_rank_for_view($programs_tree, $children_heirarchy_list);
                    if( $programs_tree && sizeof($programs_tree) > 0 ) {
                        foreach( $programs_tree as $subprograms) {
                            if( isset( $subprograms['sub_programs']) && sizeof($subprograms['sub_programs']) > 0) {
                                foreach( $subprograms['sub_programs'] as $subprogram) {
                                    // pr($subprogram);
                                    $nextProgram = new stdClass;
                                    $nextProgram->account_holder_id = $subprogram['program']->account_holder_id;
                                    $nextProgram->sub_programs = isset($subprogram['sub_programs']) ?  (object) $subprogram['sub_programs'] : null;
                                    // pr("nextProgram of RootProgram: " . $v2Program->account_holder_id);
                                    // pr($nextProgram->account_holder_id);
                                    $this->createProgram($v3_organization_id, $nextProgram, $newProgram->id);
                                }
                            }
                        }
                    }
                }
            }   else if( $v2Program->sub_programs ) {
                foreach( $v2Program->sub_programs as $subprogram) {
                    $nextProgram = new stdClass;
                    $nextProgram->account_holder_id = $subprogram['program']->account_holder_id;
                    $nextProgram->sub_programs = isset($subprogram['sub_programs']) ?  (object) $subprogram['sub_programs'] : null;
                    // pr("nextProgram of SubProgram");
                    // pr($nextProgram->account_holder_id);
                    $this->createProgram($v3_organization_id, $nextProgram, $newProgram->id);
                }
            }
        } catch(Exception $e)    {
            throw new Exception( sprintf("Error creating v3 program. Error:{$e->getMessage()} in Line: {$e->getLine()} in File: {$e->getFile()}", $e->getMessage()));
        }

        // pr("End of the program:");
        // pr($v2Program->account_holder_id);
        // pr( "****************************************************************************************************************");
        return $this->importedPrograms;
    }
}
