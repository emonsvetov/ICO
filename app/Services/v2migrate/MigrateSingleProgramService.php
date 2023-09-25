<?php

namespace App\Services\v2migrate;

use Exception;

use App\Services\ProgramService;
use App\Models\Address;
use App\Models\Program;
use App\Models\Domain;
use App\Models\DomainIP;
use App\Models\Event;
use App\Models\Invoice;
use App\Models\Leaderboard;
use stdClass;

class MigrateSingleProgramService extends MigrateProgramsService
{
    private ProgramService $programService;

    public function __construct(ProgramService $programService, MigrateProgramAccountsService $migrateProgramAccountsService)
    {
        parent::__construct($programService, $migrateProgramAccountsService);
        $this->programService = $programService;
    }
    public $newPrograms = [];
    public function addToImportMap( $account_holder_id, $key, mixed $value)    {
        $this->importMap['program'][$account_holder_id][$key] = $value;
    }
    public function migrateSingleProgram($v3_organization_id, $v2Program, $v3_parent_id = null)
    {
        if( empty($v3_organization_id) || empty($v2Program->account_holder_id) ) {
            throw new Exception( " - Required fields in v2 table to sync properly are missing. Termininating!\n");
            exit;
        }

        //Check for existence

        try{

            $create = true;

            if( $v2Program->v3_program_id )    { //find by v3 id
                $v3Program = Program::find( $v2Program->v3_program_id );
                // pr($existing->toArray());
                if( $v3Program )   {
                    printf(" - Program \"%d\" exists for v2:%d\n", $v3Program->id, $v2Program->account_holder_id);
                    $create = false;
                    //Update??
                    $this->importMap['program'][$v2Program->account_holder_id]['program'][$v3Program->id]['exists'] = 1;
                }
            }   else {
                //find by v2 id
                $v3Program = Program::where('v2_account_holder_id', $v2Program->account_holder_id )->first();
                if( $v3Program )   {
                    printf(" - Journal Event \"%d\" exists for v2: \"%d\", found via v2_journal_event_id search. Updating null v3_program_id value.\n", $v3Program->id, $v2Program->v3_program_id, $v2Program->account_holder_id);
                    $this->addV2SQL(sprintf("UPDATE `programs` SET `v3_program_id`=%d WHERE `id`=%d", $v3Program->id, $v2Program->account_holder_id));
                    $create = false;
                    $this->importMap['program'][$v2Program->account_holder_id]['program'][$v3Program->id]['exists'] = 1;
                    //Update??
                }
            }

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

            if( $create )   {
                $v3Program = $this->createV3Program( $v3_organization_id, $v2Program, $v3_parent_id);
            }

            //If it is a parent program then we will try to pull associated domains

            if( !$v3_parent_id ) { //Pull and Assign Domains if it is a root program(?)
                $domains = $this->v2db->select("SELECT d.* FROM `domains` d JOIN domains_has_programs dhp on dhp.domains_access_key = d.access_key JOIN programs p on p.account_holder_id = dhp.programs_id where p.account_holder_id = {$v2Program->account_holder_id}");

                if( $domains ) {
                    foreach ($domains as $domain) {
                        if( (int) $domain->v3_domain_id ) {
                            print(" -  - Domain:{$domain->access_key} exists in v3 as: {$domain->v3_domain_id}. Skipping..\n");
                            //TODO: update?!
                            continue;
                        }
                        //Find check
                        print("Finding domain {$domain->name} for Program\n");
                        $v3Domain = Domain::where('name', trim($domain->name))->first();
                        if( !$v3Domain )    {
                            print(" -  - Domain {$domain->name} not found, creating\n");
                            $v3Domain = Domain::create([
                                'organization_id' => $v3_organization_id,
                                'name' => $domain->name,
                                'secret_key' => $domain->secret_key,
                                'v2_domain_id' => $domain->access_key
                            ]);
                            $v3Domain->programs()->sync( [$v3Program->id], false);

                            $this->v2db->statement("UPDATE domains SET `v3_domain_id` = {$v3Domain->id} WHERE `access_key` = {$domain->access_key}");

                            //Log Import Map
                            $this->importMap['domain'][$domain->access_key] = $v3Domain->id;
                        } else {
                            print(" -  - Domain {$domain->name} found\n");
                        }

                        if( $v3Domain ) {
                            //Now get domain IPs
                            $domainIps = $this->v2db->select("SELECT `id`, `ip_address` FROM `domains_ips` where domain_access_key = {$domain->access_key}");
                            if( $domainIps ) {
                                foreach($domainIps as $domainIp) {
                                    $v3DomainIp = DomainIP::where('ip_address', $domainIp->ip_address)->where('domain_id', $v3Domain->id)->first();
                                    if( !$v3DomainIp ) {
                                        $newDomainIp = DomainIP::create([
                                            'ip_address' => $domainIp->ip_address,
                                            'domain_id' => $v3Domain->id
                                        ]);
                                        print(" -  - Domain IP {$newDomainIp->ip_address} inserted for domain:{$v3Domain->name}\n");
                                        if( !isset($this->importMap['domain'][$domain->access_key]) ) {
                                            $this->importMap['domain'][$domain->access_key] = [];
                                            if( !isset($this->importMap['domain'][$domain->access_key]['domainIp']) ) {
                                                $this->importMap['domain'][$domain->access_key]['domainIp'] = [];
                                                if( !isset($this->importMap['domain'][$domain->access_key]['domainIp'][$domainIp->id]) ) {
                                                    $this->importMap['domain'][$domain->access_key]['domainIp'][$domainIp->id] = $newDomainIp->id;
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

            // //Migration Accounts
            // {
            //     (new \App\Services\v2migrate\MigrateAccountsService)->migrateByModel($v3Program);
            //     $this->executeV2SQL(); //run for any missing run!
            // }

            // //Migration Accounts
            // {
            //     //Migration Journal events, postings, xml_event_data in this step. This step will work perfectly only if the Accounts are imported by calling "MigrateAccountsService" before running this "MigrateJournalEventsService"
            //     (new \App\Services\v2migrate\MigrateJournalEventsService)->migrateJournalEventsByModelAccounts($v3Program);
            // }

            $this->executeV2SQL(); //run for any missing run!

            //Import program users with roles
            // {
            //     $migrateUserService = app('App\Services\v2migrate\MigrateUsersService');
            //     $v2users = $migrateUserService->v2_read_list_by_program($v2Program->account_holder_id);
            //     $migrateUserService->setv2pid($v2Program->account_holder_id);
            //     $migrateUserService->setv3pid($v3Program->id);
            //     foreach( $v2users as $v2user)   {
            //         $this->importMap['program'][$v2Program->account_holder_id]['users'][] = $migrateUserService->migrateSingleUserByProgram($v2user, $v2Program);
            //     }
            // }

            // Pull Invoices
            {
                $v2Invoices = $this->v2db->select("SELECT * FROM `invoices` WHERE `program_account_holder_id` = {$v2Program->account_holder_id}");
                if( $v2Invoices ) {
                    foreach( $v2Invoices as $v2Invoice ) {
                        if( (int) $v2Invoice->v3_invoice_id ) {
                            print(" -  - Invoice:{$v2Invoice->id} exists in v3 as: {$v2Invoice->v3_invoice_id}. Skipping..\n");
                            //TODO: update?!
                            continue;
                        }
                        //Create invoice
                        $invoiceData = [
                            'program_id' => $v3Program->id,
                            'key' => $v2Invoice->key,
                            'seq' => $v2Invoice->seq,
                            'invoice_type_id' => $v2Invoice->invoice_type_id,
                            'payment_method_id' => $v2Invoice->payment_method_id,
                            'date_begin' => $v2Invoice->date_begin,
                            'date_end' => $v2Invoice->date_end,
                            'date_due' => $v2Invoice->date_due,
                            'amount' => $v2Invoice->invoice_amount,
                            'participants' => $v2Invoice->participants,
                            'new_participants' => $v2Invoice->new_participants,
                            'managers' => $v2Invoice->managers,
                            'created_at' => $v2Invoice->created,
                            'v2_invoice_id' => $v2Invoice->id,
                        ];

                        $newInvoice = Invoice::create($invoiceData);
                        $this->v2db->statement("UPDATE `invoices` SET `v3_invoice_id` = {$newInvoice->id} WHERE `id` = {$v2Invoice->id}");
                        print(" -  - Invoice:{$newInvoice->id} created for v2 invoice: {$v2Invoice->id}\n");

                        $migrateInvoiceJournalEventsService = new \App\Services\v2migrate\MigrateInvoiceJournalEventsService;
                        $migrateInvoiceJournalEventsService->migrateInvoiceJournalEventsByInvoice($v2Invoice->id, $newInvoice);

                        //Migration InvoiceJournalEvents
                    }
                }
            }

            // // Pull addresses
            // {
            //     $addresses = $this->v2db->select("SELECT * FROM `address` WHERE `account_holder_id` = {$v2Program->account_holder_id}");
            //     if( $addresses ) {
            //         foreach( $addresses as $address ) {
            //             $addressData = [
            //                 'account_holder_id' => $v3Program->account_holder_id,
            //                 'address' => $address->address,
            //                 'address_ext' => $address->address_ext,
            //                 'city' => $address->city,
            //                 'state_id' => $address->state_id,
            //                 'zip' => $address->zip,
            //                 'country_id' => $address->country_id,
            //                 'created_at' => now()
            //             ];

            //             $newAddress = Address::create($addressData);
            //             print(" -  - Address created for new Program: {$v3Program->id}\n");
            //             $this->importMap['program'][$v2Program->account_holder_id]['address'][$address->id] = $newAddress->id;
            //         }
            //     }
            // }
            // // Pull events
            // {
            //     $v2Events = $this->v2db->select("SELECT * FROM `event_templates` WHERE `program_account_holder_id` = {$v2Program->account_holder_id}");
            //     if( $v2Events ) {
            //         foreach( $v2Events as $v2Event ) {
            //             if( (int) $v2Event->v3_event_id ) {
            //                 print(" -  - Event:{$v2Event->id} exists in v3 as: {$v2Event->v3_event_id}. Skipping..\n");
            //                 //TODO: update?!
            //                 continue;
            //             }
            //             $eventData = [
            //                 'organization_id' => (int) $v3Program->organization_id,
            //                 'program_id' => (int) $v3Program->id,
            //                 'name' => $v2Event->name,
            //                 'event_type_id' => (int) $v2Event->event_type_id,
            //                 'enable' => ((int)$v2Event->event_state_id) == 13 ? 1 : 0,
            //                 'amount_override' => (int) $v2Event->amount_override,
            //                 'award_message_editable' => (int) $v2Event->award_message_editable,
            //                 'ledger_code' => (int) $v2Event->ledger_code,
            //                 'amount_override' => (int) $v2Event->amount_override,
            //                 'post_to_social_wall' => (int) $v2Event->post_to_social_wall,
            //                 'award_message_editable' => (int) $v2Event->award_message_editable,
            //                 'is_promotional' => (int) $v2Event->is_promotional,
            //                 'is_birthday_award' => (int) $v2Event->is_birthday_award,
            //                 'is_work_anniversary_award' => (int) $v2Event->is_work_anniversary_award,
            //                 'include_in_budget' => (int) $v2Event->include_in_budget,
            //                 'enable_schedule_award' => (int) $v2Event->enable_schedule_awards,
            //                 'message' => $v2Event->notification_body,
            //                 'initiate_award_to_award' => (int) $v2Event->initiate_award_to_award,
            //                 'only_internal_redeemable' => (int) $v2Event->only_internal_redeemable,
            //                 'is_team_award' => (int) $v2Event->is_team_award,
            //                 'max_awardable_amount' => 0,
            //                 'v2_event_id' => (int) $v2Event->id,
            //             ];

            //             $newEvent = Event::create($eventData);

            //             $this->v2db->statement("UPDATE `event_templates` SET `v3_event_id` = {$newEvent->id} WHERE `id` = {$v2Event->id}");

            //             print(" -  - Event:{$newEvent->id} created for new Program: {$v3Program->id}\n");

            //             $this->importMap['program'][$v2Program->account_holder_id]['event'][$v2Event->id] = $newEvent->id;
            //         }
            //     }
            // }

            // // Pull Leaderboards

            // {
            //     $v2Leaderboards = $this->v2db->select("SELECT * FROM `leaderboards` WHERE `program_account_holder_id` = {$v2Program->account_holder_id}");
            //     if( $v2Leaderboards ) {
            //         foreach( $v2Leaderboards as $v2Leaderboard ) {
            //             if( (int) $v2Leaderboard->v3_leaderboard_id ) {
            //                 print(" -  - Leaderboard:{$v2Leaderboard->id} exists in v3 as: {$v2Leaderboard->v3_leaderboard_id}. Skipping..\n");
            //                 //TODO: update?!
            //                 continue;
            //             }
            //             //Create leaderboard
            //             $leaderboardData = [
            //                 'organization_id' => $v3Program->organization_id,
            //                 'leaderboard_type_id' => $v2Leaderboard->leaderboard_type_id,
            //                 'program_id' => $v3Program->id,
            //                 'name' => $v2Leaderboard->name,
            //                 'status_id' => $v2Leaderboard->state_type_id,
            //                 'visible' => $v2Leaderboard->visible,
            //                 'one_leaderboard' => $v2Leaderboard->one_leaderboard,
            //                 'v2_leaderboard_id' => $v2Leaderboard->id,
            //             ];
            //             $newLeaderboard = Leaderboard::create($leaderboardData);
            //             //Update v3 reference field in v2 table
            //             $this->v2db->statement("UPDATE `leaderboards` SET `v3_leaderboard_id` = {$newLeaderboard->id} WHERE `id` = {$v2Leaderboard->id}");

            //             print(" -  - Leaderboard:{$newLeaderboard->id} created for program: {$v3Program->id}\n");

            //             //Log importing
            //             $this->importMap['program'][$v2Program->account_holder_id]['leaderboard'][$v2Leaderboard->id] = $newLeaderboard->id;

            //             print(" -  - Looking for Leaderboard Events for v2 Leaderboard:{$v2Leaderboard->id}\n");
            //             //Find LeaderboardEvent relations in v2 table
            //             $v2LeaderboardEvents = $this->v2db->select("SELECT e.id, e.v3_event_id FROM `leaderboards_events` le JOIN event_templates e on e.id = le.event_template_id WHERE `leaderboard_id` = {$v2Leaderboard->id}");
            //             if( $v2LeaderboardEvents ) {
            //                 $v3EventIdToSync = [];
            //                 foreach( $v2LeaderboardEvents as $v2LeaderboardEvent ) {
            //                     if( $v2LeaderboardEvent->v3_event_id ) {
            //                         // Note: "v3_event_id" from "event_templates" table. Assuming that program event has already been imported and thus "v3_event_id" field is already updated in v2 "event_templates" table before we run importer for LeaderboardEvents
            //                         array_push($v3EventIdToSync, $v2LeaderboardEvent->v3_event_id);
            //                     }
            //                 }
            //                 if( $v3EventIdToSync ) {
            //                     $newLeaderboard->events()->sync($v3EventIdToSync, false);
            //                 }
            //             }
            //         }
            //     }
            // }

            die("XxxxxxxxXxxxxxxxXxxxxxxxXxxxxxxxXxxxxxxxXxxxxxxx");

            if( !property_exists($v2Program, 'sub_programs') ) { //if root program
                $children_heirarchy_list = $this->read_list_children_heirarchy(( int )$v2Program->account_holder_id);
                pr("children_heirarchy_list count:");
                pr(count($children_heirarchy_list));
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
                                    $this->migrateSingleProgram($v3_organization_id, $nextProgram, $v3Program->id);
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
                    return $this->migrateSingleProgram($v3_organization_id, $nextProgram, $v3Program->id);
                }
            }
            $this->executeV2SQL();
            $this->executeV3SQL();
        } catch(Exception $e)    {
            throw new Exception( sprintf("Error creating v3 program. Error:{$e->getMessage()} in Line: {$e->getLine()} in File: {$e->getFile()}", $e->getMessage()));
        }

        // pr("End of the program:");
        // pr($v2Program->account_holder_id);
        // pr( "****************************************************************************************************************");
        return $v3Program;
    }

    public function createV3Program($v3_organization_id, $v2Program, $v3_parent_id)   {

        $program_config_fields_grouped = $this->read_program_config_fields_by_name ( $v2Program->account_holder_id );
        $program_config_fields = [];
        foreach($program_config_fields_grouped as $program_config_field)    {
            $program_config_fields[$program_config_field->name] = $program_config_field->value;
        }

        $extra_info = $this->read_extra_program_info ( ( int )$v2Program->account_holder_id );

        if( !$extra_info ) {
            $extra_info = (object) [
                'setup_fee' => 0,
                'allow_awarding_pending_activation_participants' => 0,
                'uses_units' => 0,
                'allow_multiple_participants_per_unit' => 0,
                'allow_managers_to_change_email' => 0,
                'allow_participants_to_change_email' => 0,
                'sub_program_groups' => 0,
                'show_internal_store' => 0,
                'allow_creditcard_deposits' => 0,
                'reserve_percentage' => 0,
                'discount_rebate_percentage' => 0,
                'expiration_rebate_percentage' => 0,
                'percent_total_spend_rebate' => 0,
                'administrative_fee' => 0,
                'administrative_fee_factor' => 0,
                'administrative_fee_calculation' => 0,
                'deposit_fee' => 0,
                'fixed_fee' => 0,
                'convenience_fee' => 0,
                'monthly_usage_fee' => 0,
                'factor_valuation' => 0,
                'accounts_receivable_email' => 0,
                'bcc_email_list' => 0,
                'cc_email_list' => 0,
                'notification_email_list' => 0,
                'allow_hierarchy_to_view_social_wall' => 0,
                'can_view_hierarchy_social_wall' => 0
            ];
        }

        $data = [
            // 'account_holder_id'                              => (int)$v2Program->account_holder_id + $max_account_holder_id + 10000,
            // 'organization_id'                                => $v2Program->organization_id,
            'parent_id'                                      => $v3_parent_id,
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
            'events_has_limits'                              => (int)$v2Program->events_has_limits,
            'event_has_category'                             => (int)$v2Program->has_category,
            'show_internal_store'                            => (int)$extra_info->show_internal_store,
            'has_promotional_award'                          => (int)$v2Program->has_promotional_award,
            'use_one_leaderboard'                            => (int)$v2Program->use_one_leaderboard,
            'use_cascading_approvals'                        => (int)$v2Program->use_cascading_approvals,
            'enable_schedule_awards'                         => (int)$v2Program->enable_schedule_awards,
            'use_budget_cascading'                           => (int)$v2Program->use_budget_cascading,
            'budget_summary'                                 => (int)$v2Program->budget_summary,
            'enable_reference_documents'                     => (int)$v2Program->enable_reference_documents,
            'consolidated_dashboard_reports'                 => (int)$v2Program->consolidated_dashboard_reports,
            'enable_global_search'                           => (int)$v2Program->enable_global_search,
            'archive_program'                                => null,
            'deactivate_account'                             => null,
            'create_invoices'                                => (int)$v2Program->create_invoices,
            'allow_creditcard_deposits'                      => (int)$extra_info->allow_creditcard_deposits,
            'reserve_percentage'                             => (float)$extra_info->reserve_percentage ? (int)$extra_info->reserve_percentage : null,
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

        try{
            $newProgram = $this->programService->create(
                $data +
                [
                    'organization_id' => $v3_organization_id,
                    'v2_account_holder_id' => $v2Program->account_holder_id,
                ]
            );

            print(" - New V3 Program created for V2 Program: {$v2Program->account_holder_id}=>{$newProgram->id}\n");

            $this->v2db->statement("UPDATE ". PROGRAMS . " SET `v3_organization_id` = {$v3_organization_id}, `v3_program_id` = {$newProgram->id} WHERE `account_holder_id` = {$v2Program->account_holder_id}");

            $v2Program->v3_program_id = $newProgram->id; //To be used in related models

            //Log Import Map
            $this->importMap['program'][$v2Program->account_holder_id]['program'] = $newProgram->toArray();

            print(" - V2 Program updated with v3 identifiying fields v3_organization_id, v3_program_id\n");
        } catch(Exception $e)    {
            throw new Exception( sprintf("Error creating v3 program. Error:{$e->getMessage()} in Line: {$e->getLine()} in File: {$e->getFile()}", $e->getMessage()));
        }
    }
}
