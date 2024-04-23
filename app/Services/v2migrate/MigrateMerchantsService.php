<?php
namespace App\Services\v2migrate;

use App\Models\AccountHolder;
use App\Models\Giftcode;
use App\Models\MediumInfo;
use App\Models\TangoOrdersApi;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Exception;

use App\Http\Traits\MerchantMediaUploadTrait;
use App\Services\MerchantService;
use App\Models\Organization;
use App\Models\Merchant;
use App\Models\Program;

class MigrateMerchantsService extends MigrationService
{
    use MerchantMediaUploadTrait;
    private MerchantService $merchantService;
    private MigrateGiftcodesService $migrateGiftcodesService;
    public $programMerchants = [];
    public $createDuplicateName = false;
    public $v2TangoAPIList = [];
    public $v3TangoUserID;
    public $v3Merchants = [];
    public $v2DublicateCodes = [];

    public $countCreatedMerchants = 0;
    public $countUpdatedMerchants = 0;
    public $countCreatedTangoApi = 0;
    public $countUpdatedTangoApi = 0;
    public $countCreatedMerchantCodes = 0;
    public $countUpdatedMerchantCodes = 0;

    public function __construct(MerchantService $merchantService, MigrateGiftcodesService $migrateGiftcodesService)
    {
        $this->merchantService = $merchantService;
        $this->migrateGiftcodesService = $migrateGiftcodesService;
        $this->v3Merchants = Merchant::all()->pluck('id', 'v2_account_holder_id')->toArray();
        parent::__construct();
    }

    /**
     * Read list tango API.
     *
     * @return array
     * @throws Exception
     */
    public function read_tango_api()
    {
        try {
            $this->v2db->statement("SET SQL_MODE=''");
            $v2Query = $this->v2db->select("SELECT * FROM `tango_orders_api`");
        } catch(\Exception $e) {
            throw new Exception( sprintf("Error select v2 tango_orders_api. Error:%s", $e->getMessage()));
        }

        $result = [];
        foreach ($v2Query as $row) {
            $result[$row->toa_id] = $row;
        }

        return $result;
    }

    /**
     * Run merchants migration.
     *
     * @return array
     * @throws Exception
     */
    public function migrate() {
        $merchantTree = [];
        try {
            $v2MerchantHierarchyList = $this->read_list_hierarchy();
            $this->v2TangoAPIList = $this->read_tango_api();

            // Get general user ID.
            $v3TangoApi = TangoOrdersApi::whereNotNull('user_id')->first();
            $this->v3TangoUserID = !blank($v3TangoApi) ? $v3TangoApi->user_id : 1;

            if (!blank($v2MerchantHierarchyList)) {
                $v2MerchantHierarchy = sort_result_by_rank($merchantTree, $v2MerchantHierarchyList, 'merchant');
                foreach ($v2MerchantHierarchy as $v2MerchantNode) {
                    $this->migrateMerchant($v2MerchantNode);
                }
            }

            return [
                'success' => TRUE,
                'info' => "
                created $this->countCreatedMerchants items, updated $this->countUpdatedMerchants items (merchants),
                created $this->countCreatedTangoApi items, updated $this->countUpdatedTangoApi items (tangoAPI).",
            ];
        } catch(Exception $e) {
            throw new Exception("Error migrating merchants. Error:{$e->getMessage()} in Line: {$e->getLine()} in File: {$e->getFile()}");
        }
    }

    /**
     * Count of new merchants.
     *
     * @param $v2MerchantHierarchy
     * @return array
     */
    public function countNewMerchantsToMigrate($v2MerchantHierarchy) {
        $v2MerchantIDs = [];
        foreach ($v2MerchantHierarchy as $v2Merchant) {
            $v2MerchantIDs[] = $v2Merchant->account_holder_id;
        }
        $v2MerchantIDs = array_unique($v2MerchantIDs);
        $v3MerchantIDs = Merchant::whereIn('v2_account_holder_id', $v2MerchantIDs)->get()->pluck('v2_account_holder_id')->toArray();

        return array_diff($v2MerchantIDs, $v3MerchantIDs);
    }

    /**
     * Get V3 Tango API ID.
     *
     * @param $v2TangoApiID
     * @return mixed
     * @throws Exception
     */
    public function getV3TangoApiIDByV2ID($v2TangoApiID)
    {

        $v2TangoAPIList = $this->v2TangoAPIList;
        $v3TangoApi = FALSE;

        if (isset($v2TangoAPIList[$v2TangoApiID])) {
            $v2TangoApiObj = $v2TangoAPIList[$v2TangoApiID];
            $v2TangoApiName = $v2TangoApiObj->toa_name;

            try {
                $v3TangoApiByID = TangoOrdersApi::where('v2_toa_id', $v2TangoApiID)->first();
            } catch(\Exception $e) {
                throw new Exception("dont find v2_toa_id, please run php artisan migrate");
            }

            $v3TangoApiData = [
                'platform_name' => $v2TangoApiObj->toa_platform_name,
                'platform_key' => $v2TangoApiObj->toa_platform_key,
                'platform_url' => $v2TangoApiObj->toa_platform_url,
                'platform_mode' => $v2TangoApiObj->toa_platform_mode,
                'account_identifier' => $v2TangoApiObj->toa_account_identifier,
                'account_number' => $v2TangoApiObj->toa_account_number,
                'customer_number' => $v2TangoApiObj->toa_customer_number,
                'udid' => $v2TangoApiObj->toa_udid,
                'etid' => $v2TangoApiObj->toa_etid,
                'status' => $v2TangoApiObj->toa_status,
                'user_id' => $this->v3TangoUserID,
                'name' => $v2TangoApiObj->toa_name,
                'is_test' => $v2TangoApiObj->toa_is_test,
                'toa_merchant_min_value' => $v2TangoApiObj->toa_merchant_min_value,
                'toa_merchant_max_value' => $v2TangoApiObj->toa_merchant_max_value,
                'v2_toa_id' => $v2TangoApiID,
            ];

            if (!blank($v3TangoApiByID)) {
                $v3TangoApi = $v3TangoApiByID;
                $v3TangoApi->update($v3TangoApiData);
                $this->countUpdatedTangoApi++;
            }
            else {
                $v3TangoApiByName = TangoOrdersApi::where('name', $v2TangoApiName)->get();
                $v3TangoApiByNameCount = $v3TangoApiByName->count();

                if ($v3TangoApiByNameCount > 1) {
                    throw new Exception("find few tango API items with the " . $v2TangoApiName . " name.");
                }
                if ($v3TangoApiByNameCount == 1) {
                    $v3TangoApi = $v3TangoApiByName->first();
                    $v3TangoApi->update($v3TangoApiData);
                    $this->countUpdatedTangoApi++;
                }
                if ($v3TangoApiByNameCount == 0) {
                    $v3TangoApi = TangoOrdersApi::create($v3TangoApiData);
                    $this->countCreatedTangoApi++;
                }
            }
        }

        if (blank($v3TangoApi)) {
            throw new Exception("Error fetching v3 tango api.");
        }

        return $v3TangoApi->id;
    }

    /**
     * Migrate a new merchant to v3.
     *
     * @param $v2MerchantNode
     * @param  null  $parent_id
     * @throws Exception
     */
    private function migrateMerchant($v2MerchantNode, $parent_id = null) {
        if( isset($v2MerchantNode['merchant']) ) {
            $v2Merchant = $v2MerchantNode['merchant'];
            $parentMerchant = Merchant::where('v2_account_holder_id', $v2Merchant->account_holder_id)->first();
            $v2TangoApiID = (int) $v2Merchant->toa_id;
            $v3TangoAPIID = $v2TangoApiID ? $this->getV3TangoApiIDByV2ID($v2TangoApiID) : 0;

            $v3MerchantData = [
                'v2_account_holder_id' => $v2Merchant->account_holder_id,
                'use_virtual_inventory' => $v2Merchant->use_virtual_inventory,
                'virtual_denominations' => $v2Merchant->virtual_denominations,
                'virtual_discount' => $v2Merchant->virtual_discount,
                'name' => $v2Merchant->name,
                'parent_id' => $parent_id,
                'description' => $v2Merchant->description,
                'website' => $v2Merchant->website,
                'redemption_instruction' => $v2Merchant->redemption_instruction,
                'redemption_callback_id' => $v2Merchant->redemption_callback_id,
                'category' => $v2Merchant->category,
                'merchant_code' => $v2Merchant->merchant_code,
                'website_is_redemption_url' => $v2Merchant->website_is_redemption_url,
                'get_gift_codes_from_root' => $v2Merchant->get_gift_codes_from_root,
                'is_default' => $v2Merchant->is_default,
                'giftcodes_require_pin' => $v2Merchant->giftcodes_require_pin,
                'display_rank_by_priority' => $v2Merchant->display_rank_by_priority,
                'display_rank_by_redemptions' => $v2Merchant->display_rank_by_redemptions,
                'requires_shipping' => $v2Merchant->requires_shipping,
                'physical_order' => $v2Merchant->physical_order,
                'is_premium' => $v2Merchant->is_premium,
                'use_tango_api' => (int) $v2Merchant->use_tango_api,
                'toa_id' => $v3TangoAPIID,
                'status' => $v2Merchant->status,
                'display_popup' => $v2Merchant->display_popup,
                'updated_at' => $v2Merchant->updated_at,
                'deleted_at' => $v2Merchant->deleted > 0 ? now()->subDays(1) : null,
            ];

            if (blank($parentMerchant)) {
                $v3MerchantData['account_holder_id'] = AccountHolder::insertGetId(['context'=>'Merchant', 'created_at' => now()]);
                $parentMerchant = Merchant::create($v3MerchantData);
                $this->countCreatedMerchants++;
                $this->v2db->unprepared("UPDATE `merchants` SET `v3_merchant_id` = {$parentMerchant->id} WHERE `account_holder_id` = {$v2Merchant->account_holder_id}");
            }
            else {
                $parentMerchant->update($v3MerchantData);
                $this->countUpdatedMerchants++;
            }

            $this->updateMerchantMedia($v2Merchant, $parentMerchant);

            if (
                isset($v2MerchantNode['sub_merchant']) &&
                !blank($v2MerchantNode['sub_merchant'])
            ) {
                foreach( $v2MerchantNode['sub_merchant'] as $v2SubMerchant ) {
                    $this->migrateMerchant($v2SubMerchant, $parentMerchant->id);
                }
            }
        }
    }

    /**
     * Update merchant media.
     */
    public function updateMerchantMedia($v2Merchant, $parentMerchant)
    {
        $icons = [];
        foreach(Merchant::MEDIA_FIELDS as $mediaField) {
            if( property_exists($v2Merchant, $mediaField) && $v2Merchant->{$mediaField}) {
                $icons[$mediaField] = $v2Merchant->{$mediaField};
            }
        }

        if( $icons ) {
            $uploads = $this->handleMerchantMediaUpload( null, $parentMerchant, false, $icons );
            if( $uploads )   {
                $parentMerchant->update($uploads);
            }
        }
    }

    public function migrateMerchantJournalEvents($v3Merchant, $v2Merchant)   {
        //Migration Journal events, postings, xml_event_data in this step. This step will work perfectly only if the Accounts are imported by calling "MigrateAccountsService" before running this "MigrateJournalEventsService"
        (new \App\Services\v2migrate\MigrateJournalEventsService)->migrateJournalEventsByModelAccounts($v3Merchant, $v2Merchant);
    }

    private function readProgramMerchantRelations( $v2_account_holder_id ) {
        $sql = "SELECT p.v3_program_id, m.v3_merchant_id, pm.* FROM `programs` p JOIN `program_merchant` pm ON p.account_holder_id = pm.program_id JOIN `merchants` m on m.account_holder_id=pm.merchant_id WHERE pm.merchant_id={$v2_account_holder_id} AND p.v3_program_id IS NOT NULL AND m.v3_merchant_id IS NOT NULL";

        //$this->v2db->statement("SET SQL_MODE=''");
        $result = $this->v2db->select($sql);
        if( $result && sizeof($result) > 0) {
            foreach( $result as $row) {
                if( !isset($this->programMerchants[$row->v3_program_id])) {
                    $this->programMerchants[$row->v3_program_id] = [];
                }
                $this->programMerchants[$row->v3_program_id][$row->v3_merchant_id] = [
                    'featured' => $row->featured,
                    'cost_to_program' => $row->cost_to_program
                ];
            }
        }
    }

    protected function fixAccountHolderIds()    {
        $v3Merchants = Merchant::whereNotNull('account_holder_id')->get();
        if( $v3Merchants )   {
            foreach( $v3Merchants as $v3Merchant )    {
                $this->fixBrokenAccountHolderByMerchant( $v3Merchant );
            }
        }
    }

    protected function fixBrokenAccountHolderByMerchant( $v3Merchant )    {
        //Find and Confirm stored account holder id in v2 db
        //I needed to do this as I found some very large account holder ids in v3 which did not exist in the v2 database. Probably they were created in some old version of v3 db then AUTO INCREMENT key was reset??
        $this->printf("Find and confirm model's stored account_holder_id with in v3 db.\n");
        $v3AccountHolder = \App\Models\AccountHolder::where('id', $v3Merchant->account_holder_id )->first();
        if( !$v3AccountHolder ) {
            $this->printf("v3Merchant->account_holder_id: %d NOT FOUND in v3 table.\n", $v3Merchant->account_holder_id);
            $this->printf("Going to create new v3 account_holder_id for model and then update: %s:%d.\n", 'Merchant', $v3Merchant->id);
            $v3Merchant->account_holder_id = \App\Models\AccountHolder::insertGetId(['context'=>'Merchant', 'created_at' => now()]);
            $v3Merchant->save();
        }   else {
            $this->printf("v3Merchant->account_holder_id: %d FOUND in v3 table.\n", $v3Merchant->account_holder_id);
        }
    }

    protected function fixv3v2RelationIds() {
        $v3Merchants = Merchant::get();
        if( $v3Merchants )   {
            foreach( $v3Merchants as $v3Merchant )    {
                $this->fixv3v2RelationIdByMerchantName( $v3Merchant );
            }
        }
    }

    protected function fixv3v2RelationIdByMerchantName( $v3Merchant )    {
        $v2Merchants = $this->v2db->select("SELECT * FROM `merchants` WHERE `name`=:name", ['name'=>$v3Merchant->name]);
        if( sizeof($v2Merchants) > 0 )   {
            $v2Merchant = current($v2Merchants);
            if( !$v2Merchant->v3_merchant_id || ($v2Merchant->v3_merchant_id != $v3Merchant->id) )  {
                $this->v2db->statement("UPDATE `merchants` SET `v3_merchant_id` = {$v3Merchant->id} WHERE `name` = :name", ['name'=>$v3Merchant->name]);
            }
            if( !$v3Merchant->v2_account_holder_id || ($v2Merchant->account_holder_id != $v3Merchant->v2_account_holder_id) ) {
                $v3Merchant->v2_account_holder_id = $v2Merchant->account_holder_id;
                $v3Merchant->save();
            }
        }
    }

    /**
     * Sync merchants to a program.
     *
     * @param $v2AccountHolderID
     * @return bool
     * @throws Exception
     */
    public function syncProgramMerchantRelations($v2AccountHolderID) {

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

        $v3MerchantIDs = Merchant::all()->pluck('id', 'id')->toArray();

        $v2ProgramMerchants = $this->v2db->select(
            sprintf("
                SELECT p.v3_program_id, m.v3_merchant_id, pm.*
                FROM `programs` p
                JOIN `program_merchant` pm ON p.account_holder_id = pm.program_id
                JOIN `merchants` m on m.account_holder_id=pm.merchant_id
                WHERE p.account_holder_id=%d AND m.v3_merchant_id IS NOT NULL", $v2AccountHolderID)
        );

        if(!empty($v2ProgramMerchants)) {
            $programMerchants = [];
            foreach($v2ProgramMerchants as $v2ProgramMerchant) {
                if (!$v2ProgramMerchant->v3_merchant_id) {
                    throw new Exception("v3_merchant_id in V2 table merchants not found.");
                }
                if (!$v2ProgramMerchant->v3_program_id) {
                    throw new Exception("v3_program_id in V2 table programs not found.");
                }

                if ($v3MerchantIDs[$v2ProgramMerchant->v3_merchant_id] ?? FALSE) {
                    $programMerchants[$v2ProgramMerchant->v3_merchant_id] = [
                        'featured' => $v2ProgramMerchant->featured,
                        'cost_to_program' => $v2ProgramMerchant->cost_to_program
                    ];
                }
                else {
                    throw new Exception("Merchant with ID : $v2ProgramMerchant->v3_merchant_id not found in V3. Please run global migrations for migrate a new merchants.");
                }
            }
            if ($programMerchants) {
                try {
                    $v3Program = Program::where('account_holder_id', $v3AccountHolderID)->first();
                    $v3Program->merchants()->sync($programMerchants, false);
                    $countProgramMerchants = count($programMerchants);
                    $result['success'] = TRUE;
                    $result['info'] = "sync $countProgramMerchants items.";
                } catch (\Exception $exception) {
                    throw new Exception("Sync merchants to a program is failed.");
                }
            }
        }

        return $result;
    }

    /**
     * Migrate available codes to a merchant.
     *
     * @param $v3Merchant
     * @throws Exception
     */
    public function migrateAvailableCodes($v3Merchant)
    {
        $v2AvailableMerchantCodes = $this->getV2AvailableMerchantCodes($v3Merchant);
        if (!empty($v2AvailableMerchantCodes)) {
            foreach ($v2AvailableMerchantCodes as $v2AvailableMerchantCode) {

                if ($v2AvailableMerchantCode->v3_gift_code_id && $v2AvailableMerchantCode->code) {
                    // it may already be synchronized in another way
                    $v3MediumInfo = Giftcode::withoutGlobalScopes()->where('id', $v2AvailableMerchantCode->v3_gift_code_id)
                        ->where('code', $v2AvailableMerchantCode->code)
                        ->first();
                    if($v3MediumInfo){
                        continue;
                    }
                }

                $v2MerchantID = $v2AvailableMerchantCode->merchant_account_holder_id;
                $v2RedeemedMerchantID = $v2AvailableMerchantCode->redeemed_merchant_account_holder_id;
                $v2RedeemedUserID = $v2AvailableMerchantCode->redeemed_account_holder_id;

                $code = $v2AvailableMerchantCode->code;
                $this->v2DublicateCodes[$code] = isset($this->v2DublicateCodes[$code]) ? ($this->v2DublicateCodes[$code] + 1) : 1;
                $countCode = $this->v2DublicateCodes[$code];

                // Fix for duplicate code as FXXXXXX.
                if ($countCode > 1) {
                    $code .= '_' . $countCode;
                }

                $v3MediumInfoData = [
                    'purchase_date' => $v2AvailableMerchantCode->purchase_date,
                    'redemption_date' => $v2AvailableMerchantCode->redemption_date,
                    'expiration_date' => $v2AvailableMerchantCode->expiration_date,
                    'hold_until' => $v2AvailableMerchantCode->hold_until,
                    'redemption_value' => $v2AvailableMerchantCode->redemption_value,
                    'cost_basis' => $v2AvailableMerchantCode->cost_basis,
                    'discount' => $v2AvailableMerchantCode->discount,
                    'sku_value' => $v2AvailableMerchantCode->sku_value,
                    'pin' => $v2AvailableMerchantCode->pin,
                    'redemption_url' => $v2AvailableMerchantCode->redemption_url,
                    'encryption' => $v2AvailableMerchantCode->encryption,
                    'code' => $code,
                    'merchant_id' => (int) $v2MerchantID ? $this->getV3MerchantID($v2MerchantID) : NULL,
                    'redeemed_merchant_id' => (int) $v2RedeemedMerchantID ? $this->getV3MerchantID($v2MerchantID) : NULL,
                    'redeemed_program_id' => NULL, // all column NULL in v2
                    'redeemed_user_id' => (int) $v2RedeemedUserID ? $this->getV3RedeemedUserID($v2RedeemedUserID) : NULL,
                    'factor_valuation' => $v2AvailableMerchantCode->factor_valuation,
                    'medium_info_is_test' => $v2AvailableMerchantCode->medium_info_is_test,
                    'redemption_datetime' => $v2AvailableMerchantCode->redemption_datetime,
                    'purchased_by_v2' => TRUE,
                    'tango_request_id' => $v2AvailableMerchantCode->tango_request_id,
                    'tango_reference_order_id' => $v2AvailableMerchantCode->tango_reference_order_id,
                    'v2_medium_info_id' => $v2AvailableMerchantCode->id,
                    'virtual_inventory' => $v2AvailableMerchantCode->virtual_inventory,
                    'v2_sync_status' => Giftcode::SYNC_STATUS_SUCCESS,
                ];

                $m3MediumInfo = MediumInfo::where('v2_medium_info_id', $v2AvailableMerchantCode->id)->first();
                if (blank($m3MediumInfo)) {
                    MediumInfo::create($v3MediumInfoData);
                    $this->countCreatedMerchantCodes++;
                }
                else {
                    $m3MediumInfo->update($v3MediumInfoData);
                    $this->countUpdatedMerchantCodes++;
                }

            }
        }
    }

    /**
     * Get v3 merchant ID.
     *
     * @param $v2MerchantID
     * @return mixed
     * @throws Exception
     */
    public function getV3MerchantID($v2MerchantID)
    {
        try {
            return $this->v3Merchants[$v2MerchantID];
        } catch (\Exception $e)  {
            throw new Exception("v2 merchant in v3 not found.");
        }
    }

    /**
     * Get v3 User ID.
     *
     * @param $v2UserID
     * @return mixed
     * @throws Exception
     */
    public function getV3RedeemedUserID($v2UserID)
    {
        $v2Sql = "SELECT u.* FROM users u WHERE u.account_holder_id = {$v2UserID} LIMIT 1";
        $result = $this->v2db->select($v2Sql);
        $v2User = reset($result);

        $v3UserID = $v2User->v3_user_id ?? FALSE;
        if (!$v3UserID) {
            $v3User = User::where('email', $v2User->email)->first();
            $v3UserID = $v3User->id ?? FALSE;
        }

        if (!$v3UserID) {
            throw new Exception("Sync available codes for merchants is failed.");
        }

        return $v3UserID;
    }

    /**
     * Migrate wrapper available gift codes.
     */
    public function availableGiftCodes()
    {
        $merchantTree = [];
        try {
            $v2MerchantHierarchyList = $this->read_list_hierarchy();

            if (!blank($v2MerchantHierarchyList)) {
                $v2MerchantHierarchy = sort_result_by_rank($merchantTree, $v2MerchantHierarchyList, 'merchant');
                foreach ($v2MerchantHierarchy as $v2MerchantNode) {
                    $this->migrateGiftCodeMerchant($v2MerchantNode);
                }
            }

            return [
                'success' => TRUE,
                'info' => "created $this->countCreatedMerchantCodes items, updated $this->countUpdatedMerchantCodes items.",
            ];
        } catch(Exception $e) {
            throw new Exception("Error migrating merchants. Error:{$e->getMessage()} in Line: {$e->getLine()} in File: {$e->getFile()}");
        }
    }

    /**
     * Migrate available gift codes to a merchant.
     *
     * @param $v2MerchantNode
     * @param  null  $parent_id
     * @throws Exception
     */
    public function migrateGiftCodeMerchant($v2MerchantNode, $parent_id = null)
    {
        if(isset($v2MerchantNode['merchant']) ) {

            $v2Merchant = $v2MerchantNode['merchant'];
            $parentMerchant = Merchant::where('v2_account_holder_id', $v2Merchant->account_holder_id)->first();

            $this->migrateAvailableCodes($parentMerchant);

            if (
                isset($v2MerchantNode['sub_merchant']) &&
                !blank($v2MerchantNode['sub_merchant'])
            ) {
                foreach( $v2MerchantNode['sub_merchant'] as $v2SubMerchant ) {
                    $this->migrateGiftCodeMerchant($v2SubMerchant, $parentMerchant->id);
                }
            }
        }
    }

}
