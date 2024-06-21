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
     * Sync program settings.
     *
     * @param $v2AccountHolderID
     * @return array
     * @throws Exception
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
        $v2AccountHolderID = $v3Program->v2_account_holder_id ?? FALSE;
        $subPrograms = $v3Program->children ?? [];

        if ($v2AccountHolderID) {
            $this->updateProgramSettings($v3Program, [
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
     * @throws Exception
     */
    public function  updateProgramSettings($v3Program, $v2ProgramData)
    {
        $v2ProgramExtraInfo = (array) $v2ProgramData['v2ProgramExtraInfo'];
        $v2ProgramConfigFields = (array) $v2ProgramData['v2ProgramConfigFields'];
        $v2Program = (array) $v2ProgramData['v2Program'];
        $v2Address = (array) reset($v2ProgramData['v2Address']);
        $v2ProgramTransactionFees = (array) $v2ProgramData['v2ProgramTransactionFees'];

        $v2Settings = array_merge($v2ProgramExtraInfo, $v2Program, $v2ProgramConfigFields, $v2Address);

        // Fix.
        $v2Settings['uses_leaderboards'] = $v2Settings['uses_leaderbaords'] ?? FALSE;
        $v2Settings['allow_award_peers_not_logged_into'] = $v2Settings['peer_award_seperation'] ?? FALSE;
        $v2Settings['allow_search_peers_not_logged_into'] = $v2Settings['peer_search_seperation'] ?? FALSE;
        $v2Settings['bill_direct'] = !$v2Settings['bill_direct'] ?? FALSE;
        $v2Settings['account_holder_id'] = $v3Program->account_holder_id;
        $v2Settings['remove_social_from_pending_deactivation'] = $v2Settings['social_wall_remove_social'] ?? FALSE;
        $v2Settings['social_wall_separation'] = $v2Settings['social_wall_seperation'] ?? FALSE;
        $v2Settings['uses_leaderboards'] = $v2Settings['uses_leaderbaords'] ?? FALSE;
        $v2Settings['allow_award_peers_not_logged_into'] = $v2Settings['peer_award_seperation'] ?? FALSE;
        $v2Settings['allow_search_peers_not_logged_into'] = $v2Settings['peer_search_seperation'] ?? FALSE;
        $v2Settings['bcc_email_list'] = isset($v2Settings['bcc_email_list']) ? trim($v2Settings['bcc_email_list']) : '';
        $v2Settings['cc_email_list'] = isset($v2Settings['cc_email_list']) ? trim($v2Settings['cc_email_list']) : '';

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
                    $v3ProgramData[$field] = $v2Settings[$field];
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
}
