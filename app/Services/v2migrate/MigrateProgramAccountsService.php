<?php

namespace App\Services\v2migrate;

use App\Models\Address;
use App\Models\Invoice;
use App\Models\ProgramExtra;
use App\Models\ProgramTransactionFee;
use App\Services\ProgramService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Exception;

use App\Models\JournalEvent;
use App\Models\Program;
use App\Models\Account;
use App\Models\Posting;
use Illuminate\Support\Facades\Schema;

class MigrateProgramAccountsService extends MigrationService
{
    public array $importedProgramAccounts = [];
    private ProgramService $programService;
    public $countUpdatedPrograms = 0;
    public $countBrokenPrograms = 0;

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

        $this->printf("Starting program accounts migration\n\n",);
        $v2RootPrograms = $this->read_list_all_root_program_ids($programArgs);
        if (!$v2RootPrograms) {
            throw new Exception("No program found. v2AccountHolderID: {$v2AccountHolderID}");
        }

        $this->migrateProgramAccounts($v2RootPrograms);

        return [
            'success' => TRUE,
            'info' => "migrated " . count($this->importedProgramAccounts) . " items",
        ];

    }

    public function migrateProgramAccounts(array $v2RootPrograms): void
    {
        foreach ($v2RootPrograms as $v2RootProgram) {
            $this->printf("Starting migrations for root program: {$v2RootProgram->account_holder_id}\n",);
            $sql = sprintf("SELECT * FROM accounts WHERE account_holder_id = %d", $v2RootProgram->account_holder_id);
            $v2Accounts = $this->v2db->select($sql);
            $this->syncOrCreateAccounts($v2RootProgram, $v2Accounts);

            $subPrograms = $this->read_list_children_heirarchy(( int )$v2RootProgram->account_holder_id);
            foreach ($subPrograms as $subProgram) {
                $sql = sprintf("SELECT * FROM accounts WHERE account_holder_id = %d", $subProgram->account_holder_id);
                $v2Accounts = $this->v2db->select($sql);
                $this->syncOrCreateAccounts($subProgram, $v2Accounts);
            }
        }
    }

    public function syncOrCreateAccounts($v2Program, $v2Accounts)
    {
        $v3Program = Program::findOrFail($v2Program->v3_program_id);
        foreach ($v2Accounts as $v2Account) {
            $v3Account = Account::where([
                'account_holder_id' => $v3Program->account_holder_id,
                'account_type_id' => $v2Account->account_type_id,
                'finance_type_id' => $v2Account->finance_type_id,
                'medium_type_id' => $v2Account->medium_type_id,
                'currency_type_id' => $v2Account->currency_type_id,
            ])->first();
            $v3AccountId = $v3Account->id ?? null;
            if (!$v3AccountId) {
                $v3AccountId = Account::getIdByColumns([
                    'account_holder_id' => $v3Program->account_holder_id,
                    'account_type_id' => $v2Account->account_type_id,
                    'finance_type_id' => $v2Account->finance_type_id,
                    'medium_type_id' => $v2Account->medium_type_id,
                    'currency_type_id' => $v2Account->currency_type_id,
                    'v2_account_id' => $v2Account->id,
                ]);

            }

            if ($v2Account->v3_account_id != $v3AccountId) {
                $this->v2db->statement(sprintf("UPDATE `accounts` SET `v3_account_id`=%d WHERE `id`=%d", $v3AccountId, $v2Account->id));
            }

            $this->importedProgramAccounts[] = $v3AccountId;
        }
    }

    /**
     * v2 Get extra program info.
     *
     * @param $v2AccountHolderID
     * @return mixed
     */
    public function readExtraProgramInfo($v2AccountHolderID)
    {
        $v2Sql = "SELECT
			p.*
			FROM
				programs_extra AS p
            LEFT JOIN domains AS d ON d.access_key = p.default_domain_access_key
			WHERE
				p.program_account_holder_id = {$v2AccountHolderID}
			LIMIT 1";

        $extra = [];
        $result = $this->v2db->select($v2Sql);
        if ($result) {
            $result = reset($result);
            $field_types = [
                'program_account_holder_id' => 'int',
                'factor_valuation' => 'int',
                'points_over_budget' => 'int',
                'bill_direct' => 'bool',
                'reserve_percentage' => 'int',
                'setup_fee' => 'float',
                'monthly_usage_fee' => 'float',
                'discount_rebate_percentage' => 'float',
                'expiration_rebate_percentage' => 'float',
                'budget_number' => 'float',
                'alarm_percentage' => 'int',
                'administrative_fee' => 'float',
                'administrative_fee_factor' => 'float',
                'administrative_fee_calculation' => 'string',
                'fixed_fee' => 'float',
                'monthly_recurring_points_billing_percentage ' => 'int',
                'allow_multiple_participants_per_unit' => 'bool',
                'uses_units' => 'bool',
                'allow_awarding_pending_activation_participants' => 'bool',
                'allow_creditcard_deposits' => 'bool',
                'air_show_programs_tab' => 'bool',
                'air_show_manager_award_tab' => 'bool',
                'air_premium_cost_to_program' => 'bool',
                'air_show_all_event_list' => 'bool'
            ];
            $extra = cast_fieldtypes($result, $field_types);
        }
        return $extra;
    }

    /**
     * Sync program settings.
     *
     * @param $v2AccountHolderID
     */
    public function syncProgramHierarchySettings($v2AccountHolderID)
    {
        $result = [
            'success' => FALSE,
            'info' => '',
        ];

        $v3Program = Program::where('v2_account_holder_id', $v2AccountHolderID)->first();
        $v3AccountHolderID = $v3Program->account_holder_id ?? NULL;

        // Checking if v3 program is exists.
        if (empty($v3AccountHolderID)) {
            throw new Exception("v3 program with ID: " . $v2AccountHolderID . " not found.");
        }

        $this->syncSubProgram($v3Program);

        try {
            $result['success'] = TRUE;
            $result['info'] = "update $this->countUpdatedPrograms items, broken $this->countBrokenPrograms items";
        } catch (\Exception $exception) {
            throw new Exception("Sync program settings is failed.");
        }

        return $result;
    }

    /**
     * Sync program hierarchy.
     *
     * @param $v3Program
     * @throws Exception
     */
    public function syncSubProgram($v3Program)
    {
        $programs = $this->programService->getHierarchyByProgramId($organization = FALSE, $v3Program->id)->toArray();
        $subPrograms = $programs[0]["children"] ?? FALSE;

        $v3SubProgram = Program::find($v3Program->id);
        $v2AccountHolderID = $v3SubProgram->v2_account_holder_id ?? FALSE;

        if ($v2AccountHolderID) {
            $this->updateProgramSettings($v3SubProgram, [
                'v2ProgramConfigFields' => $this->readConfigFieldsByName($v2AccountHolderID),
                'v2ProgramExtraInfo' => $this->readExtraProgramInfo($v2AccountHolderID),
                'v2Program' => $this->get_program_info($v2AccountHolderID),
                'v2Address' => $this->getAddressInfo($v2AccountHolderID),
                'v2ProgramTransactionFees' => $this->getProgramTransactionFees($v2AccountHolderID),
            ]);
            $this->countUpdatedPrograms++;
        }
        else {
            $this->countBrokenPrograms++;
        }

        if (!empty($subPrograms)) {
            foreach ($subPrograms as $subProgram) {
                $this->syncSubProgram($subProgram);
            }
        }
    }

    /**
     * Update program settings.
     *
     * @param $v3Program
     * @param $v2ProgramConfigFields
     * @param $v2ProgramExtraInfo
     */
    public function  updateProgramSettings($v3Program, $v2ProgramData)
    {
        $v2ProgramExtraInfo = (array) $v2ProgramData['v2ProgramExtraInfo'];
        $v2ProgramConfigFields = (array) $v2ProgramData['v2ProgramConfigFields'];
        $v2Program = (array) $v2ProgramData['v2Program'];
        $v2Address = (array) reset($v2ProgramData['v2Address']);
        $v2ProgramTransactionFees = (array) $v2ProgramData['v2ProgramTransactionFees'];

        $v2Settings = array_merge($v2Program, $v2ProgramConfigFields, $v2ProgramExtraInfo, $v2Address);

        // Fix.
        $v2Settings['uses_leaderboards'] = $v2Settings['uses_leaderbaords'] ?? FALSE;
        $v2Settings['allow_award_peers_not_logged_into'] = $v2Settings['peer_award_seperation'] ?? FALSE;
        $v2Settings['allow_search_peers_not_logged_into'] = $v2Settings['peer_search_seperation'] ?? FALSE;
        $v2Settings['bill_direct'] = !$v2Settings['bill_direct'] ?? FALSE;
        $v2Settings['account_holder_id'] = $v3Program->account_holder_id;

        ksort($v2Settings);

        $v3ProgramAttributes = Schema::getColumnListing((new Program)->getTable());
        $v3ProgramExtrasAttributes = Schema::getColumnListing((new ProgramExtra)->getTable());
        $v3ProgramAddressAttributes = Schema::getColumnListing((new Address)->getTable());

        $settingsForUpdate = [
            'uses_social_wall',
            'allow_hierarchy_to_view_social_wall',
            'can_view_hierarchy_social_wall',
            'managers_can_post_social_wall_messages',
            'social_wall_separation',
            'remove_social_from_pending_deactivation',
            'uses_leaderboards',
            'allow_view_leaderboards_not_logged_into',
            'share_siblings_leader_board',
            'use_one_leaderboard',
            'uses_peer2peer',
            'allow_award_peers_not_logged_into',
            'allow_search_peers_not_logged_into',
            'uses_hierarchy_peer2peer',
            'share_siblings_peer2peer',
            'uses_goal_tracker',
            'uses_units',
            'allow_multiple_participants_per_unit',
            'enable_how_are_you_feeling',
            'enable_referrals',
            'allow_milestone_award',
            'use_budget_cascading',
            'budget_summary',
            'country',
            'invoice_for_awards',
            'create_invoices',
            'allow_creditcard_deposits',
            'tier_amount',
            'transaction_fee',
            'reserve_percentage',
            'discount_rebate_percentage',
            'expiration_rebate_percentage',
            'percent_total_spend_rebate',
            'bill_direct',
            'administrative_fee_factor',
            'administrative_fee_calculation',
            'administrative_fee',
            'deposit_fee',
            'fixed_fee',
            'convenience_fee',
            'monthly_usage_fee',
            'accounts_receivable_email',
            'invoice_po_number',
            'send_balance_threshold_notification',
            'balance_threshold',
            'low_balance_email',
            'name',
            'external_id',
//            'type',
            'sub_program_groups',
            'archive_program',
            'factor_valuation',
            'default_user',
            'corporate_entity',
            'status',
            'enable_global_search',
            'deactivate_account',
            'public_contact_email',
            'prefix',
            'bcc_email_list',
            'cc_email_list',
            'notification_email_list',
            'address',
            'address_ext',
            'city',
            'zip',
            'state_id',
            'events_has_limits',
            'event_has_category',
            'has_promotional_award',
            'enable_uploads_while_awarding',
            'enable_schedule_awards',
            'custom_expire_offset',
            'custom_expire_units',
            'annual_expire_month',
            'annual_expire_day',
            'send_points_expire_notices',
            'allocate',
            'amount_override_limit_percent',
            'awards_limit_amount_override',
//            'expiration_rule_id',
//            'unknown',
        ];

        $canUpdate = [];
        $notCanUpdate = [];
        foreach ($v3ProgramAddressAttributes as $field => $value) {
            if (
                in_array($field, $settingsForUpdate) &&
                in_array($field, $v2Settings)
            ) {
                $canUpdate[] = $field;
            }
            else {
                $notCanUpdate[] = $field;
            }
        }
        sort($canUpdate);
        sort($notCanUpdate);

        /**
         * uses_social_wall +
         * allow_hierarchy_to_view_social_wall ++
         * can_view_hierarchy_social_wall ++
         * managers_can_post_social_wall_messages +
         * social_wall_separation +
         * remove_social_from_pending_deactivation +
         * uses_leaderboards +
         * allow_view_leaderboards_not_logged_into +
         * share_siblings_leader_board +
         * use_one_leaderboard +
         * uses_peer2peer +
         * allow_award_peers_not_logged_into +
         * allow_search_peers_not_logged_into +
         * uses_hierarchy_peer2peer +
         * share_siblings_peer2peer +
         * uses_goal_tracker +
         * uses_units ++
         * allow_multiple_participants_per_unit ++
         * enable_how_are_you_feeling +
         * enable_referrals +
         * allow_milestone_award +
         */

        /**
         * country
         * tier_amount +
         * use_budget_cascading +
         * budget_summary +
         * invoice_for_awards +
         * create_invoices +
         * allow_creditcard_deposits ++
         * transaction_fee +
         * reserve_percentage ++
         * discount_rebate_percentage ++
         * expiration_rebate_percentage ++
         * percent_total_spend_rebate ++
         * bill_direct +
         * administrative_fee_factor ++
         * administrative_fee_calculation ++
         * administrative_fee ++
         * deposit_fee ++
         * fixed_fee ++
         * convenience_fee ++
         * monthly_usage_fee ++
         * accounts_receivable_email ++
         * invoice_po_number +
         * send_balance_threshold_notification +
         * balance_threshold +
         * low_balance_email +
         */

        /**
         * name +
         * external_id +
         * type +
         * sub_program_groups ++
         * archive_program +
         * factor_valuation ++
         * corporate_entity +
         * enable_global_search +
         * deactivate_account +
         * public_contact_email +
         * prefix +
         * bcc_email_list ++
         * cc_email_list ++
         * notification_email_list ++
         * address +
         * address_ext +
         * city +
         * zip +
         * state_id +
         * status
         * default_user
         */

        /**
         * events_has_limits +
         * event_has_category +
         * has_promotional_award +
         * enable_schedule_awards +
         * custom_expire_offset +
         * custom_expire_units +
         * annual_expire_month +
         * annual_expire_day +
         * send_points_expire_notices +
         * amount_override_limit_percent +
         * awards_limit_amount_override +
         * expiration_rule_id +??
         * unknown
         * allocate
         * enable_uploads_while_awarding
         */

        try {
            $v3ProgramExtraData = [];
            $v3ProgramData = [];
            $v3ProgramAddressData = [];
            foreach ($v3ProgramExtrasAttributes as $field) {
                if (isset($v2Settings[$field])) {
                    $v3ProgramExtraData[$field] = $v2Settings[$field];
                }
            }
            foreach ($v3ProgramAttributes as $field) {
                if (isset($v2Settings[$field])) {
                    $v3ProgramData[$field] = empty($v2Settings[$field]) ? 0 : $v2Settings[$field];
                }
            }
            foreach ($v3ProgramAddressAttributes as $field) {
                if (isset($v2Settings[$field])) {
                    $v3ProgramAddressData[$field] = $v2Settings[$field];
                }
            }

            $v3Program->programExtras()->updateOrCreate(['program_account_holder_id' => $v3Program->v2_account_holder_id], $v3ProgramExtraData);
            $v3Program->address()->updateOrCreate(['account_holder_id' => $v3Program->account_holder_id], $v3ProgramAddressData);
            $v3Program->update($v3ProgramData);

            $v3ProgramTransactionFee = [];
            ProgramTransactionFee::where('program_id', $v3Program->id)->delete();
            if (!empty($v2ProgramTransactionFees)) {
                foreach ($v2ProgramTransactionFees as $item) {
                    $v3ProgramTransactionFee[] = [
                        'program_id' => $v3Program->id,
                        'tier_amount' => $item->tier_amount,
                        'transaction_fee' => $item->transaction_fee,
                    ];
                }
                ProgramTransactionFee::insert($v3ProgramTransactionFee);
            }

        } catch (\Exception $exception) {
            throw new Exception("update program settings is failed.");
        }
    }

    /**
     * v2 read_config_field_rules
     */
    public function readConfigFieldRules($configCustomFieldID)
    {
        $v2Sql = "
            SELECT
                config_fields_has_rules.argument,
                config_fields_has_rules.config_fields_id,
                config_fields_has_rules.custom_fields_rules_id,
                custom_field_rules.rule,
                custom_field_rules.requires_argument
            FROM
                config_fields_has_rules
            INNER JOIN
                config_fields ON config_fields.id = config_fields_has_rules.config_fields_id
            INNER JOIN
                custom_field_rules ON custom_field_rules.id = config_fields_has_rules.custom_fields_rules_id
            WHERE
                config_fields_has_rules.`config_fields_id` = " . $configCustomFieldID;


        return $this->v2db->select($v2Sql);
    }

    /**
     * v2 Get transaction fees.
     */
    public function getProgramTransactionFees($v2AccountHolderID)
    {
        $v2Sql = "
            SELECT * FROM programs_transaction_fees
            WHERE program_account_holder_id = {$v2AccountHolderID}
        ";

        return $this->v2db->select($v2Sql);
    }

    /**
     * v2 Get address.
     *
     * @param $v2AccountHolderID
     * @return array
     */
    public function getAddressInfo($v2AccountHolderID)
    {
        $v2Sql = "
			SELECT
				address.state_id,
				address.country_id,
				address.address,
				address.address_ext,
				address.city,
				address.zip,
                states.name as state,
                states.code as state_code,
                countries.name as country_name,
                countries.iso_code_2 as country
			FROM
				address
				JOIN countries ON countries.id = address.country_id
		    LEFT JOIN states ON states.id = address.state_id
			WHERE
				address.account_holder_id = {$v2AccountHolderID}
        ";

        return $this->v2db->select($v2Sql);
    }

    /**
     * v2 read_list_config_fields
     */
    public function readListConfigFields()
    {
        $v2Sql = "
            SELECT
                config_fields.*,
                custom_field_types.type
            FROM
                config_fields
            LEFT JOIN
                custom_field_types ON custom_field_types.id =  config_fields.custom_field_type_id
        ";

        $result = $this->v2db->select($v2Sql);
        if (is_array ( $result ) && count ( $result ) > 0) {
            foreach ( $result as &$row ) {
                $row->rules = $this->readConfigFieldRules( ( int ) $row->id );
                $row->rules_string = '';
                if (is_array ( $row->rules ) && count ( $row->rules ) > 0) {
                    $arr_rules = array ();
                    foreach ( $row->rules as $rule ) {
                        $rule_string = $rule->rule;
                        if ($rule->requires_argument) {
                            $rule_string .= '[' . $rule->argument . ']';
                        }
                        $arr_rules [] = $rule_string;
                    }
                    $row->rules_string = implode ( '|', $arr_rules );
                }
            }
            foreach ( $result as &$row2 ) {
                $field_types = array (
                    'id' => 'int',
                    'custom_field_type_id' => 'int',
                    'require_hierarchy_unique' => 'bool',
                    'must_inherit' => 'bool'
                );
                switch ($row2->type) {
                    case "int" :
                        $field_types ['default_value'] = 'int';
                        break;
                    case "float" :
                        $field_types ['default_value'] = 'float';
                        break;
                    case "bool" :
                        $field_types ['default_value'] = 'bool';
                        break;
                }
                $row2 = cast_fieldtypes ( $row2, $field_types );
            }
        }

        return $result;
    }

    /**
     * v2 Returns a programs custom field.
     *
     * @return array|void
     */
    public function readConfigFieldsByName($v2AccountHolderID)
    {
        $config_field_names = [];
        $getMoreConfigField = false; //TODO.

        if (! isset ( $config_field_names ) || ! is_array ( $config_field_names ) || count ( $config_field_names ) == 0) {
            $all_config_fields = $this->readListConfigFields();
            foreach ( $all_config_fields as $config_field ) {
                $config_field_names [] = $config_field->name;
            }
        }
        $config_field_names = array_unique ( $config_field_names );

        $v2Sql = "
        SELECT programs_config_fields.*,
               if(config_fields.access_parent_value = 0 AND programs_config_fields.program_account_holder_id != {$v2AccountHolderID},
                  config_fields.default_value, programs_config_fields.value) as value,
               config_fields.*,
               custom_field_types.type,
               GROUP_CONCAT(if(requires_argument, CONCAT(custom_field_rules.rule, '[', argument, ']'), custom_field_rules.rule)
                            SEPARATOR '|')                                   AS rules_string
        FROM program_paths
                 INNER JOIN
             programs_config_fields ON programs_config_fields.program_account_holder_id = program_paths.ancestor
                 INNER JOIN
             config_fields ON config_fields.id = programs_config_fields.config_field_id
                 INNER JOIN
             custom_field_types ON custom_field_types.id = config_fields.custom_field_type_id
                 LEFT JOIN
             config_fields_has_rules ON config_fields_has_rules.config_fields_id = config_fields.id
                 LEFT JOIN
             custom_field_rules ON custom_field_rules.id = config_fields_has_rules.custom_fields_rules_id
        WHERE ((program_paths.descendant = {$v2AccountHolderID} AND must_inherit != 1) OR
               (must_inherit = 1 AND programs_config_fields.program_account_holder_id = {$v2AccountHolderID}))
        AND config_fields.`name` IN
              ('csv_importer_add_participants', 'csv_importer_update_participants', 'csv_importer_deactivate_participants',
               'csv_importer_award_participants', 'csv_importer_add_goal_progress', 'csv_importer_add_goals_to_participants',
               'csv_importer_add_and_award_participants', 'uses_peer2peer', 'uses_hierarchy_peer2peer', 'uses_social_wall',
               'allow_hierarchy_to_view_social_wall', 'can_view_hierarchy_social_wall', 'uses_goal_tracker',
               'uses_leaderbaords', 'google_custom_search_engine_cx', 'crm_company_tag_id', 'crm_reminder_email_delay_1',
               'crm_reminder_email_delay_2', 'crm_reminder_email_delay_3', 'crm_reminder_email_delay_4',
               'can_post_social_wall_comments', 'program_managers_can_invite_participants', 'awards_limit_amount_override',
               'amount_override_limit_percent', 'invoice_po_number', 'csv_importer_add_new_participants',
               'csv_import_option_use_organization_uid', 'csv_import_option_use_external_program_id',
               'csv_importer_add_participant_redemptions', 'csv_importer_add_events',
               'csv_importer_add_active_participants_no_email', 'share_siblings_social_wall', 'peer_award_seperation',
               'peer_search_seperation', 'social_wall_seperation', 'leaderboard_seperation', 'point_ratio_seperation',
               'share_siblings_leader_board', 'share_siblings_peer2peer', 'csv_importer_peer_to_peer_awards',
               'manager_can_award_all_program_participants', 'show_all_social_wall',
               'csv_importer_add_and_award_participants_with_event', 'managers_can_post_social_wall_messages',
               'team_management_view', 'brochures_enable_on_participant', 'display_brochures_across_hierarchy',
               'csv_importer_add_managers', 'referral_notification_recipient_management', 'social_wall_remove_social',
               'self_enrollment_enable', 'mobile_app_management', 'allow_cross_hierarchy_display_filtering',
               'csv_importer_update_employer_yes', 'csv_importer_custom_file', 'csv_importer_clean_participants_yes',
               'default_user_status_display_filtering')
        GROUP BY programs_config_fields.program_account_holder_id, config_fields.id
        ORDER BY config_fields.group, program_paths.path_length
                ";

        $this->v2db->statement("SET SQL_MODE=''");
        $result = $this->v2db->select($v2Sql);

        $return_data = array ();
        if (isset ( $result ) && is_array ( $result ) && count ( $result ) > 0) {
            foreach ( $result as $row ) {
                // Exit loop if we have everything we came here for
                if (count ( $return_data ) == count ( $config_field_names )) {
                    break;
                }
                // If we have already grabbed a config item by this name then skip it
                if (isset ( $return_data [$row->name] )) {
                    continue;
                }
                // Set the inherited flag
                if ($row->program_account_holder_id == $v2AccountHolderID) {
                    $row->inherited = false;
                } else {
                    $row->inherited = true;
                }
                $field_types = array (
                    'id' => 'int',
                    'program_account_holder_id' => 'int',
                    'custom_field_type_id' => 'int',
                    'require_hierarchy_unique' => 'bool'
                );
                switch ($row->type) {
                    case "int" :
                        $field_types ['default_value'] = 'int';
                        $field_types ['value'] = 'int';
                        break;
                    case "float" :
                        $field_types ['default_value'] = 'float';
                        $field_types ['value'] = 'float';
                        break;
                    case "bool" :
                        $field_types ['default_value'] = 'bool';
                        $field_types ['value'] = 'bool';
                        break;
                }
                $row = cast_fieldtypes ( $row, $field_types );
                $return_data [$row->name] = $row;
            }
        }
        // Determine what rows are missed and grab the defaults
        if (! is_array ( $return_data ) || count ( $return_data ) == 0 || count ( $return_data ) != count ( $config_field_names )) {
            // Collect a list of all of the config fields that we don't have
            $missing_config_fields = array ();
            foreach ( $config_field_names as $config_field_name ) {
                if (! isset ( $return_data [$config_field_name] )) {
                    $missing_config_fields [] = $config_field_name;
                }
            }

            // DAE-31
            if($getMoreConfigField == true){
                // if fields are missing, then use the defaults
                $more_config_fields = $this->config_fields_model->read_config_fields_by_name ( $missing_config_fields );
                // Update the inherited flag on all of the fields we just received
                // and use the default value from the field as the value
                if (isset ( $more_config_fields ) && is_array ( $more_config_fields ) && count ( $more_config_fields ) > 0) {
                    foreach ( $more_config_fields as $more_config_field ) {
                        $more_config_field->inherited = true;
                        $more_config_field->value = $more_config_field->default_value;
                        $return_data [$more_config_field->name] = $more_config_field;
                    }
                }
            }

        }
        ksort ( $return_data );

        $programConfigFields = [];
        foreach($return_data as $program_config_field)    {
            $programConfigFields[$program_config_field->name] = $program_config_field->value;
        }

        // get the row in object type
        return $programConfigFields;
    }

}
