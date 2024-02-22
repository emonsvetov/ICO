<?php
namespace App\Services\v2migrate;

use App\Models\AccountHolder;
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

    public function __construct(MerchantService $merchantService, MigrateGiftcodesService $migrateGiftcodesService)
    {
        $this->merchantService = $merchantService;
        $this->migrateGiftcodesService = $migrateGiftcodesService;
        parent::__construct();
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
            $newMerchants = $this->countNewMerchantsToMigrate($v2MerchantHierarchyList);
            $countNewMerchants = count($newMerchants);

            if (!blank($v2MerchantHierarchyList)) {
                $v2MerchantHierarchy = sort_result_by_rank($merchantTree, $v2MerchantHierarchyList, 'merchant');
                foreach ($v2MerchantHierarchy as $v2MerchantNode) {
                    $this->migrateMerchant($v2MerchantNode);
                }
            }

            return [
                'success' => TRUE,
                'info' => "migrated $countNewMerchants items",
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

            if (blank($parentMerchant)) {
                $merchant_account_holder_id = AccountHolder::insertGetId(['context'=>'Merchant', 'created_at' => now()]);

                $v3MerchantData = [
                    'v2_account_holder_id' => $v2Merchant->account_holder_id,
                    'account_holder_id' => $merchant_account_holder_id,
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
                    'toa_id' => (int) $v2Merchant->toa_id,
                    'status' => $v2Merchant->status,
                    'display_popup' => $v2Merchant->display_popup,
                    'updated_at' => $v2Merchant->updated_at,
                    'deleted_at' => $v2Merchant->deleted > 0 ? now()->subDays(1) : null,
                ];

                $parentMerchant = Merchant::create($v3MerchantData);
                $this->v2db->unprepared("UPDATE `merchants` SET `v3_merchant_id` = {$parentMerchant->id} WHERE `account_holder_id` = {$v2Merchant->account_holder_id}");

                $icons = [];
                foreach(Merchant::MEDIA_FIELDS as $mediaField) {
                    if( property_exists($v2Merchant, $mediaField) && $v2Merchant->{$mediaField}) {
                        $icons[$mediaField] = $v2Merchant->{$mediaField};
                    }
                }

                if ($icons) {
                    $uploads = $this->handleMerchantMediaUpload( null, $parentMerchant, false, $icons );
                    if( $uploads )   {
                        $parentMerchant->update($uploads);
                    }
                }
            }

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

    /**
     * Get merchants from v2.
     *
     * @param  int  $offset
     * @param  int  $limit
     * @param  string  $order_column
     * @param  string  $order_direction
     * @return array
     * @throws Exception
     */
    public function read_list_hierarchy($offset = 0, $limit = 9999999, $order_column = 'name', $order_direction = 'asc') {
		$statement = "
			SELECT
            *,
	       (SELECT
                COALESCE(GROUP_CONCAT(DISTINCT ranking_merchant.account_holder_id
                    ORDER BY `" . MERCHANT_PATHS . "`.path_length DESC), `" . MERCHANTS . "`.account_holder_id ) AS 'rank'
            FROM
                merchant_paths
            LEFT JOIN
                `" . MERCHANTS . "` AS ranking_merchant ON `" . MERCHANT_PATHS . "`.ancestor = ranking_merchant.account_holder_id
            WHERE `" . MERCHANT_PATHS . "`.descendant = `" . MERCHANTS . "`.account_holder_id
                ) as 'rank'
        , ( SELECT
            MAX(COALESCE(`ranking_path_length`.path_length, 0)) as path_length
        FROM
            `" . MERCHANT_PATHS . "`
        LEFT JOIN
            `" . MERCHANT_PATHS . "` AS ranking_path_length ON `" . MERCHANT_PATHS . "`.descendant = ranking_path_length.descendant and `" . MERCHANT_PATHS . "`.ancestor != ranking_path_length.ancestor

        WHERE `" . MERCHANT_PATHS . "`.descendant = `" . MERCHANTS . "`.account_holder_id
            ) as path_length
			FROM

				`" . MERCHANTS . "`
            WHERE `" . MERCHANTS . "`.`deleted` = 0";

        $statement .= " GROUP BY
                " . MERCHANTS . ".account_holder_id
            ORDER BY
                `{$order_column}` {$order_direction}, " . MERCHANTS . ".name
			LIMIT
				{$offset}, {$limit};
			";

        try {
            $this->v2db->statement("SET SQL_MODE=''");
            $result = $this->v2db->select($statement);
        } catch(\Exception $e) {
            throw new Exception( sprintf("Error fetching v2 merchants. Error:%s", $e->getMessage()));
        }
        $return_data = [];
		foreach ( $result as $row ) {
			$row = $this->cast_merchant_fieldtypes ( $row );
			$return_data[] = $row;
		}
		return $return_data;
	}

    /**
     * @param $row
     * @return mixed
     */
	private function cast_merchant_fieldtypes($row) {
		$field_types = array (
            'account_holder_id' => 'int',
            'website_is_redemption_url' => 'int',
            'get_gift_codes_from_root' => 'bool',
            'is_default' => 'bool',
            'giftcodes_require_pin' => 'bool',
            'display_rank_by_priority' => 'int',
            'display_rank_by_redemptions' => 'int',
            'requires_shipping' => 'bool',
            'physical_order' => 'bool'
		);
		return cast_fieldtypes ($row, $field_types);
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
            throw new Exception("v3 program or v2_account_holder_id not found.");
        }

        $v3MerchantIDs = Merchant::all()->pluck('id', 'id')->toArray();

        $v2ProgramMerchants = $this->v2db->select(
            sprintf("
                SELECT p.v3_program_id, m.v3_merchant_id, pm.*, m.name merchant_name
                FROM `programs` p
                JOIN `program_merchant` pm ON p.account_holder_id = pm.program_id
                JOIN `merchants` m on m.account_holder_id=pm.merchant_id
                WHERE p.account_holder_id=%d AND m.v3_merchant_id IS NOT NULL", $v2AccountHolderID)
        );

        $programMerchants = [];

        if(!blank($v2ProgramMerchants)) {
            foreach ($v2ProgramMerchants as $v2ProgramMerchant) {
                if (!$v2ProgramMerchant->v3_merchant_id) {
                    throw new Exception("v3_merchant_id in V2 table merchants not found.");
                }
                if (!$v2ProgramMerchant->v3_program_id) {
                    throw new Exception("v3_program_id in V2 table programs not found.");
                }

                if ($v3MerchantIDs[$v2ProgramMerchant->v3_merchant_id] ?? false) {
                    $programMerchants[$v2ProgramMerchant->v3_merchant_id] = [
                        'featured' => $v2ProgramMerchant->featured,
                        'cost_to_program' => $v2ProgramMerchant->cost_to_program
                    ];
                } else {
                    throw new Exception("Merchant with ID : $v2ProgramMerchant->merchant_name not found in V3. Please run global migrations for migrate a new merchants.");
                }
            }
        }

        try {
            $v3Program->merchants()->sync($programMerchants);
            $countProgramMerchants = count($programMerchants);
            $result['success'] = TRUE;
            $result['info'] = "was sync $countProgramMerchants items";
        } catch (\Exception $exception) {
            throw new Exception("Sync merchants to a program is failed.");
        }

        return $result;
    }
}
