<?php

namespace App\Services\v2migrate;

use App\Models\AccountHolder;
use Exception;
use App\Services\ProgramService;
use App\Models\Organization;
use App\Models\Program;

class MigrateProgramsService extends MigrationService
{
    public array $importedPrograms = [];
    private ProgramService $programService;

    public function __construct(ProgramService $programService)
    {
        parent::__construct();
        $this->programService = $programService;
    }

    /**
     * @param int $v2AccountHolderID
     * @return array
     * @throws Exception
     */
    public function migrate(int $v2AccountHolderID): array
    {
        if (!$v2AccountHolderID) {
            throw new Exception("Wrong data provided. v2AccountHolderID: {$v2AccountHolderID}");
        }
        $programArgs = ['program' => $v2AccountHolderID];

        $this->fixAccountHolderIds();
        $this->printf("Starting program migration\n\n",);
        $v2RootPrograms = $this->read_list_all_root_program_ids($programArgs);
        if (!$v2RootPrograms) {
            throw new Exception("No program found. v2AccountHolderID: {$v2AccountHolderID}");
        }

        $this->migratePrograms($v2RootPrograms);

        return [
            'success' => TRUE,
            'info' => "migrated " . count($this->importedPrograms) . " items",
        ];
    }

    /**
     * @param array $v2RootPrograms
     * @return void
     * @throws Exception
     */
    public function migratePrograms(array $v2RootPrograms): void
    {
        foreach ($v2RootPrograms as $v2RootProgram) {
            $this->printf("Starting migrations for root program: {$v2RootProgram->account_holder_id}\n",);
            $v2Program = $this->get_program_info($v2RootProgram->account_holder_id);
            if (!$v2Program) {
                throw new Exception("Program info not found. v2RootProgram: {$v2RootProgram->account_holder_id}");
            }

            $v2Program = $this->findOrCreateOrganization($v2Program);
            $this->migrateSingleProgram($v2Program);

            $subPrograms = $this->read_list_children_heirarchy(( int )$v2Program->account_holder_id);
            foreach ($subPrograms as $subProgram) {
                $v2SubProgram = $this->get_program_info($subProgram->account_holder_id);
                $v2SubProgram->v3_organization_id = $v2Program->v3_organization_id;
                $v2Parent = $this->get_program_info($subProgram->direct_ancestor);
                $v3ParentId = $v2Parent->v3_program_id ?? null;
                $this->migrateSingleProgram($v2SubProgram, $v3ParentId);
            }
        }
    }

    /**
     * @param object $v2Program
     * @param int|null $v3_parent_id
     * @return void
     */
    public function migrateSingleProgram(object $v2Program, int $v3_parent_id = null)
    {
        $v3Program = $this->getV3Program($v2Program);
        if ($v3Program) {
            $v3Program->parent_id = $v3_parent_id;
            $v3Program->organization_id = $v2Program->v3_organization_id;
            $v3Program->save();
            $this->printf("v3Program({$v3Program->id}) updated for v2 program: {$v2Program->name}.\n",);
        } else {
            $v3Program = $this->createV3Program($v2Program, $v3_parent_id);
            $this->printf("v3Program({$v3Program->id}) created for v2 program: {$v2Program->name}.\n",);
        }
        $this->importedPrograms[] = $v3Program;
    }

    /**
     * @param object $v2Program
     * @return null|Program
     */
    public function getV3Program(object $v2Program): ?Program
    {
        if ($v2Program->v3_program_id) {
            $v3Program = Program::find($v2Program->v3_program_id);
            if ($v3Program) {
                if ($v3Program->v2_account_holder_id !== $v2Program->account_holder_id) {
                    $v3Program->v2_account_holder_id = $v2Program->account_holder_id;
                    $v3Program->save();
                }
            }
        } else {
            $v3Program = Program::where('v2_account_holder_id', $v2Program->account_holder_id)->first();
            if ($v3Program) {
                $this->v2db->statement("
                    UPDATE `programs`
                    SET `v3_program_id` = ?
                    WHERE `account_holder_id` = ?",
                    [$v3Program->id, $v2Program->account_holder_id]
                );
            }
        }
        return $v3Program;
    }

    /**
     * @param object $rootProgram
     * @return object
     */
    public function findOrCreateOrganization(object $rootProgram): object
    {
        $v3Organization = Organization::find($rootProgram->v3_organization_id);
        $createOrganization = $v3Organization ? false : true;
        if ($createOrganization) {
            $v3Organization = Organization::where('name', $rootProgram->name)->first();
            if (!$v3Organization) {
                $v3Organization = Organization::create([
                    'name' => $rootProgram->name
                ]);
                $this->v2db->statement("
                        UPDATE `programs`
                        SET `v3_organization_id` = {$v3Organization->id}
                        WHERE `account_holder_id` = {$rootProgram->account_holder_id}
                    ");
            }
            $rootProgram->v3_organization_id = $v3Organization->id;
        }
        return $rootProgram;
    }

    protected function fixAccountHolderIds(): void
    {
        $v3Programs = Program::whereNotNull('account_holder_id')
            ->select('programs.*')
            ->leftJoin('account_holders', 'account_holders.id', '=', 'programs.account_holder_id')
            ->whereNull('account_holders.id')
            ->get();
        foreach ($v3Programs as $v3Program) {
            $v3Program->account_holder_id = AccountHolder::insertGetId(['context' => 'Program', 'created_at' => now()]);
            $v3Program->save();
        }
    }

    /**
     * @param object $v2Program
     * @param int|null $v3_parent_id
     * @return mixed
     * @throws Exception
     */
    public function createV3Program($v2Program, int $v3_parent_id = null)
    {
        $program_config_fields_grouped = $this->read_program_config_fields_by_name($v2Program->account_holder_id);
        $program_config_fields = [];
        foreach ($program_config_fields_grouped as $program_config_field) {
            $program_config_fields[$program_config_field->name] = $program_config_field->value;
        }

        $extra_info = $this->read_extra_program_info(( int )$v2Program->account_holder_id);

        if (!$extra_info) {
            $extra_info = (object)[
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
            'parent_id' => $v3_parent_id,
            'name' => $v2Program->name,
            'type' => 'default',
            'status_id' => (int)$v2Program->program_state_id,
            'setup_fee' => $extra_info->setup_fee,
            'is_pay_in_advance' => 1,
            'invoice_for_awards' => $v2Program->invoice_for_awards,
            'is_add_default_merchants' => 1,
            'public_contact_email' => $v2Program->public_contact_email,
            'prefix' => $v2Program->prefix,
            'external_id' => $v2Program->external_id,
            'corporate_entity' => $v2Program->corporate_entity,
            'expiration_rule_id' => $v2Program->expiration_rule_id ? (int)$v2Program->expiration_rule_id : null,
            'custom_expire_offset' => $v2Program->custom_expire_offset ? (int)$v2Program->custom_expire_offset : null,
            'custom_expire_units' => $v2Program->custom_expire_units,
            'annual_expire_month' => $v2Program->annual_expire_month ? (int)$v2Program->annual_expire_month : null,
            'annual_expire_day' => $v2Program->annual_expire_day ? (int)$v2Program->annual_expire_day : null,
            'allow_awarding_pending_activation_participants' => $extra_info->allow_awarding_pending_activation_participants,
            'uses_units' => $extra_info->uses_units,
            'allow_multiple_participants_per_unit' => $extra_info->allow_multiple_participants_per_unit,
            'send_points_expire_notices' => $v2Program->send_points_expire_notices,
            'points_expire_notice_days' => $v2Program->points_expire_notice_days ? (int)$v2Program->points_expire_notice_days : null,
            'allow_managers_to_change_email' => $extra_info->allow_managers_to_change_email,
            'allow_participants_to_change_email' => $extra_info->allow_participants_to_change_email,
            'sub_program_groups' => $extra_info->sub_program_groups,
            'events_has_limits' => (int)$v2Program->events_has_limits,
            'event_has_category' => (int)$v2Program->has_category,
            'show_internal_store' => (int)$extra_info->show_internal_store,
            'has_promotional_award' => (int)$v2Program->has_promotional_award,
            'use_one_leaderboard' => (int)$v2Program->use_one_leaderboard,
            'use_cascading_approvals' => (int)$v2Program->use_cascading_approvals,
            'enable_schedule_awards' => (int)$v2Program->enable_schedule_awards,
            'use_budget_cascading' => (int)$v2Program->use_budget_cascading,
            'budget_summary' => (int)$v2Program->budget_summary,
            'enable_reference_documents' => (int)$v2Program->enable_reference_documents,
            'consolidated_dashboard_reports' => (int)$v2Program->consolidated_dashboard_reports,
            'enable_global_search' => (int)$v2Program->enable_global_search,
            'archive_program' => null,
            'deactivate_account' => null,
            'create_invoices' => (int)$v2Program->create_invoices,
            'allow_creditcard_deposits' => (int)$extra_info->allow_creditcard_deposits,
            'reserve_percentage' => (float)$extra_info->reserve_percentage ? (int)$extra_info->reserve_percentage : null,
            'discount_rebate_percentage' => $extra_info->discount_rebate_percentage ? (int)$extra_info->discount_rebate_percentage : null,
            'expiration_rebate_percentage' => $extra_info->expiration_rebate_percentage ? (int)$extra_info->expiration_rebate_percentage : null,
            'percent_total_spend_rebate' => $extra_info->percent_total_spend_rebate ? (int)$extra_info->percent_total_spend_rebate : null,
            'bill_parent_program' => null,
            'administrative_fee' => $extra_info->administrative_fee ? (int)$extra_info->administrative_fee : null,
            'administrative_fee_factor' => $extra_info->administrative_fee_factor ? (int)$extra_info->administrative_fee_factor : null,
            'administrative_fee_calculation' => $extra_info->administrative_fee_calculation ? $extra_info->administrative_fee_calculation : 'participants',
            'transaction_fee' => null,
            'deposit_fee' => $extra_info->deposit_fee ? (int)$extra_info->deposit_fee : null,
            'fixed_fee' => $extra_info->fixed_fee ? (int)$extra_info->fixed_fee : null,
            'convenience_fee' => $extra_info->convenience_fee ? (int)$extra_info->convenience_fee : null,
            'monthly_usage_fee' => $extra_info->monthly_usage_fee ? (int)$extra_info->monthly_usage_fee : null,
            'factor_valuation' => (int)$extra_info->factor_valuation,
            'accounts_receivable_email' => $extra_info->accounts_receivable_email,
            'bcc_email_list' => trim($extra_info->bcc_email_list),
            'cc_email_list' => trim($extra_info->cc_email_list),
            'notification_email_list' => trim($extra_info->notification_email_list),
            'allow_hierarchy_to_view_social_wall' => $extra_info->allow_hierarchy_to_view_social_wall,
            'can_post_social_wall_comments' => $program_config_fields['can_post_social_wall_comments'],
            'can_view_hierarchy_social_wall' => $extra_info->can_view_hierarchy_social_wall,
            'managers_can_post_social_wall_messages' => $program_config_fields['managers_can_post_social_wall_messages'],
            'share_siblings_social_wall' => $program_config_fields['share_siblings_social_wall'],
            'show_all_social_wall' => $program_config_fields['show_all_social_wall'],
            'social_wall_separation' => null,
            'uses_social_wall' => $program_config_fields['uses_social_wall'],
            'amount_override_limit_percent' => $program_config_fields['amount_override_limit_percent'] ? $program_config_fields['amount_override_limit_percent'] : null,
            'awards_limit_amount_override' => $program_config_fields['awards_limit_amount_override'],
            'brochures_enable_on_participant' => $program_config_fields['brochures_enable_on_participant'],
            'crm_company_tag_id' => $program_config_fields['crm_company_tag_id'] ? $program_config_fields['crm_company_tag_id'] : null,
            'crm_reminder_email_delay_1' => $program_config_fields['crm_reminder_email_delay_1'] ? $program_config_fields['crm_reminder_email_delay_1'] : null,
            'crm_reminder_email_delay_2' => $program_config_fields['crm_reminder_email_delay_2'] ? $program_config_fields['crm_reminder_email_delay_2'] : null,
            'crm_reminder_email_delay_3' => $program_config_fields['crm_reminder_email_delay_3'] ? $program_config_fields['crm_reminder_email_delay_3'] : null,
            'crm_reminder_email_delay_4' => $program_config_fields['crm_reminder_email_delay_4'] ? $program_config_fields['crm_reminder_email_delay_4'] : null,
            'csv_import_option_use_external_program_id' => $program_config_fields['csv_import_option_use_external_program_id'],
            'csv_import_option_use_organization_uid' => $program_config_fields['csv_import_option_use_organization_uid'],
            'google_custom_search_engine_cx' => $program_config_fields['google_custom_search_engine_cx'],
            'invoice_po_number' => $program_config_fields['invoice_po_number'],
            'leaderboard_seperation' => $program_config_fields['leaderboard_seperation'],
            'share_siblings_leader_board' => $program_config_fields['share_siblings_leader_board'],
            'uses_leaderboards' => null,
            'manager_can_award_all_program_participants' => $program_config_fields['manager_can_award_all_program_participants'],
            'program_managers_can_invite_participants' => $program_config_fields['program_managers_can_invite_participants'],
            'peer_award_seperation' => $program_config_fields['peer_award_seperation'],
            'peer_search_seperation' => $program_config_fields['peer_search_seperation'],
            'share_siblings_peer2peer' => $program_config_fields['share_siblings_peer2peer'],
            'uses_hierarchy_peer2peer' => $program_config_fields['uses_hierarchy_peer2peer'],
            'uses_peer2peer' => $program_config_fields['uses_peer2peer'],
            'point_ratio_seperation' => $program_config_fields['point_ratio_seperation'] ? $program_config_fields['point_ratio_seperation'] : null,
            'team_management_view' => $program_config_fields['team_management_view'],
            'uses_goal_tracker' => $program_config_fields['uses_goal_tracker'],
            'enable_upload_while_awarding' => false,
            'amount_override_percentage' => 0,
            'remove_social_from_pending_deactivation' => false,
            'is_demo' => false,
            'allow_award_peers_not_logged_into' => false,
            'allow_search_peers_not_logged_into' => false,
            'allow_view_leaderboards_not_logged_into' => false,
        ];

        $newProgram = $this->programService->create(
            $data +
            [
                'organization_id' => $v2Program->v3_organization_id,
                'v2_account_holder_id' => $v2Program->account_holder_id,
            ]
        );

        $this->v2db->statement("
                UPDATE `programs`
                SET
                    `v3_organization_id` = {$v2Program->v3_organization_id},
                    `v3_program_id` = {$newProgram->id}
                WHERE `account_holder_id` = {$v2Program->account_holder_id}
            ");

        $v2Program->v3_program_id = $newProgram->id; //To be used in related models
        return $newProgram;
    }
}
