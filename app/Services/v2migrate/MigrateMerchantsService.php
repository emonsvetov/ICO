<?php
namespace App\Services\v2migrate;

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
    public $fetchMerchantMedia = false;

    public function __construct(MerchantService $merchantService, MigrateGiftcodesService $migrateGiftcodesService)
    {
        $this->merchantService = $merchantService;
        $this->migrateGiftcodesService = $migrateGiftcodesService;
        parent::__construct();
    }

    public function migrate( $args = [] ) {
        $this->fixv3v2RelationIds();
        $this->fixAccountHolderIds();

        $this->printf("In MigrateMerchantsService.php start migrate\n");
        // $v2Merchants = $this->v2db->select("SELECT * FROM `merchants`");
        $merchant_tree = array ();
        $v2MerchantHierarchy = $this->read_list_hierarchy();
        if( sizeof($v2MerchantHierarchy) > 0) {
            $v2MerchantHierarchy = sort_result_by_rank($merchant_tree, $v2MerchantHierarchy, 'merchant');
        }
        // pr(count($v2MerchantHierarchy));
        // inilog($v2MerchantHierarchy);
        //
        if( $v2MerchantHierarchy ) {
            // DB::beginTransaction();
            // $this->v2db->beginTransaction();
            $i=0;
            try {
                foreach ($v2MerchantHierarchy as $v2MerchantAccountHolderId => $v2MerchantNode) {
                    // pr($v2MerchantAccountHolderId);
                    // pr($v2ListItem);
                    // pr($v2MerchantNode['merchant']->account_holder_id);
                    // if( $v2MerchantNode['merchant']->account_holder_id == 326675 ) {
                        // pr($v2MerchantNode);
                        $this->migrateMerchant($v2MerchantNode);
                        //
                    // }
                    //
                    // if( $i++ > 2)
                    $this->executeV2SQL();
                    // if( $this->importedCount > 3 )
                }
                // pr($this->programMerchants);

                ## COMMENT1: We run them by program ID so program-merchant sync is performed on Program level. Check MigrateSingleProgramService for "syncProgramMerchantRelations" method. The following code is commented out since we are not running GLOBAL migration to migrate ALL programs at once.

                // if( $this->programMerchants ) {
                //     $proramIds = array_keys($this->programMerchants);
                //     $programs = Program::whereIn('id', $proramIds)->get();
                //     foreach($programs as $program) {
                //         $this->printf("Syncing \"programs_merchants\" for program:{$program->id}");
                //         // pr($this->programMerchants[$program->id]);
                //         $program->merchants()->sync($this->programMerchants[$program->id], false);
                //     }
                // }
                // DB::commit();
                // $this->v2db->commit();
                $this->executeV2SQL();
            }catch(Exception $e)    {
                // DB::rollback();
                // $this->v2db->rollBack();
                throw new Exception("Error migrating merchants. Error:{$e->getMessage()} in Line: {$e->getLine()} in File: {$e->getFile()}");
            }
        }
    }

    private function migrateMerchant($v2MerchantNode, $parent_id = null) {
        $this->setDebug(true);
        if( isset($v2MerchantNode['merchant']) ) {
            $v2Merchant = $v2MerchantNode['merchant'];
            if( !property_exists($v2Merchant, "v3_merchant_id") ) {
                throw new Exception( "The `v3_merchant_id` field is required in v2 `merchants` table to sync properly.\n(Did your run migrations?)\nTermininating!");

            }
            $create = true;

            $this->printf("Finding Merchant {$v2Merchant->name} in v3\n");
            $v3Merchant = Merchant::where('name', 'LIKE', $v2Merchant->name)->first();
            if( $v3Merchant ) {
                $this->printf("v2Merchant:{$v2Merchant->account_holder_id} found in v3 by name. Updating..\n");
                if( !$v3Merchant->v2_account_holder_id || ($v2Merchant->account_holder_id != $v3Merchant->v2_account_holder_id) ) {
                    $v3Merchant->v2_account_holder_id = $v2Merchant->account_holder_id;
                    $v3Merchant->save();
                }
                if( !$v2Merchant->v3_merchant_id || ($v2Merchant->v3_merchant_id != $v3Merchant->id) ) {
                    // $this->v2db->statement("UPDATE `merchants` SET `v3_merchant_id` = {$v3Merchant->id} WHERE `account_holder_id` = {$v2Merchant->account_holder_id}");
                    $this->v2db->unprepared("UPDATE `merchants` SET `v3_merchant_id` = {$v3Merchant->id} WHERE `account_holder_id` = {$v2Merchant->account_holder_id}");
                }
                //TODO: more updates?!
                $create = false;
            }

            // $v3Merchant = null;
            // if( $v2Merchant->v3_merchant_id ) {
            //     $this->printf("v2Merchant:v3_merchant_id is NOT NULL. Confirming with v3..\n\n");
            //     $v3MerchantById = Merchant::find( $v2Merchant->v3_merchant_id );
            //     if( $v3MerchantById )   {
            //         $this->printf("v3Merchant found by v2Merchant:v3_merchant_id. Matching name..\n\n");
            //         //Let re-confirm with name
            //         if( $v3MerchantById->name !== $v2Merchant->name)    {
            //             $v3MerchantByName = Merchant::where('name', 'LIKE', $v2Merchant->name);
            //             $v3Merchant = $v3MerchantByName;
            //         }   else {
            //             $v3Merchant = $v3MerchantById;
            //         }
            //         if( $v3Merchant )   {
            //             $this->printf("v3Merchant found by v2Merchant:v3_merchant_id. Skipping..\n\n");
            //             $create = false;
            //             if( $v3Merchant->v2_account_holder_id != $v2Merchant->account_holder_id ) {
            //                 $v3Merchant->v2_account_holder_id = $v2Merchant->account_holder_id;
            //                 $v3Merchant->save();
            //             }
            //         }
            //     }
            //     //TODO: update?!
            // }   else {
            //     //Lets find by v2 id
            //     $v3Merchant = Merchant::where( 'v2_account_holder_id', $v2Merchant->account_holder_id )->first();

            //     if( $v3Merchant )   {
            //         $create = false;
            //         $this->v2db->unprepared(sprintf("UPDATE `merchants` SET `v3_merchant_id`=%d WHERE `account_holder_id`=%d", $v3Merchant->id, $v2Merchant->account_holder_id));
            //     }
            // }

            // if( $create ) {
            //     if( !$this->createDuplicateName )   {
            //         $this->printf("Finding Merchant {$v2Merchant->name} in v3\n");
            //         $v3Merchant = Merchant::where('name', trim($v2Merchant->name))->first();
            //         if( $v3Merchant ) {
            //             $this->printf("v2Merchant:{$v2Merchant->account_holder_id} exists in v3 as: {$v2Merchant->v3_merchant_id}. Updating..\n");
            //             if( !$v3Merchant->v2_account_holder_id || $v2Merchant->account_holder_id != $v3Merchant->v2_account_holder_id ) {
            //                 $v3Merchant->v2_account_holder_id = $v2Merchant->account_holder_id;
            //                 $v3Merchant->save();
            //             }
            //             if( !$v2Merchant->v3_merchant_id ) {
            //                 // $this->v2db->statement("UPDATE `merchants` SET `v3_merchant_id` = {$v3Merchant->id} WHERE `account_holder_id` = {$v2Merchant->account_holder_id}");
            //                 $this->v2db->unprepared("UPDATE `merchants` SET `v3_merchant_id` = {$v3Merchant->id} WHERE `account_holder_id` = {$v2Merchant->account_holder_id}");
            //             }
            //             //TODO: more updates?!
            //             $create = false;
            //         }
            //     }
            // }

            if( $create ) {

                $v3MerchantData = [
                    'v2_account_holder_id' => $v2Merchant->account_holder_id,
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

                $this->printf("Creating v3Merchant for v2Merchant:%s.\n\n", $v2Merchant->account_holder_id);

                $newMerchant = (new \App\Models\Merchant)->createAccount( $v3MerchantData );
                //Update v2 for reference column
                // $this->v2db->statement("UPDATE `merchants` SET `v3_merchant_id` = {$newMerchant->id} WHERE `account_holder_id` = {$v2Merchant->account_holder_id}");
                $this->v2db->unprepared("UPDATE `merchants` SET `v3_merchant_id` = {$newMerchant->id} WHERE `account_holder_id` = {$v2Merchant->account_holder_id}");

                if( $newMerchant ) {

                    $icons = [];

                    if( $this->fetchMerchantMedia ) {
                        foreach(Merchant::MEDIA_FIELDS as $mediaField) {
                            if( property_exists($v2Merchant, $mediaField) && $v2Merchant->{$mediaField}) {
                                $icons[$mediaField] = $v2Merchant->{$mediaField};
                            }
                        }

                        if( $icons ) {
                            $this->printf("Logo/Icons detected. Uploading, be patient..\n");
                            $uploads = $this->handleMerchantMediaUpload( null, $newMerchant, false, $icons );
                            if( $uploads )   {
                                $this->printf(sprintf("%d Logo/Icons uploaded successfully.\n", count($uploads)));
                                $newMerchant->update( $uploads );
                            }
                        }
                    }

                    $this->importedCount++;
                    $this->printf("New merchant for v2Merchant: {$v2Merchant->account_holder_id} created successfully!\n");

                    // $this->readProgramMerchantRelations( $v2Merchant->account_holder_id );
                    // Check COMMENT1
                }
            }

            $v3Merchant = $newMerchant ?? $v3Merchant;

            if( $v3Merchant ) {
                // pr($v2Merchant );
                $this->printf("Checking accounts for v2Merchant:%s\n\n", $v2Merchant->account_holder_id);
                (new \App\Services\v2migrate\MigrateAccountsService)->migrateByModel($v3Merchant);
                // $this->migrateMerchantJournalEvents($v3Merchant, $v2Merchant);
                //It will try to migrate ALL journalEvents for the merchant but we do not want them ALL. We want only events related ONLY to already pulled Models (Program/User).
                // if( isset($v2MerchantNode['sub_merchant']) && sizeof($v2MerchantNode['sub_merchant']) > 0 ) {
                //     foreach( $v2MerchantNode['sub_merchant'] as $v2submerchant ) {
                //         $this->migrateMerchant($v2submerchant, $v3Merchant->id);
                //     }
                // }
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

        // pr($statement);

        try{
            $this->v2db->statement("SET SQL_MODE=''");
            $result = $this->v2db->select($statement);
        } catch(\Exception $e) {
            throw new Exception( sprintf("Error fetching v2 merchants. Error:%s", $e->getMessage()));
        }
        $return_data = [];
		foreach ( $result as $row ) {
			$row = $this->cast_merchant_fieldtypes ( $row );
			$return_data [] = $row;
		}
		return $return_data;
	}
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
		return cast_fieldtypes ( $row, $field_types );
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
     * @param $v3AccountHolderID
     * @return bool
     * @throws Exception
     */
    public function syncProgramMerchantRelations($v2AccountHolderID, $v3AccountHolderID) {

        $result = FALSE;

        // Checking if v3 program is exists.
        if (empty($v3AccountHolderID)) {
            throw new Exception("v3 program not found.");
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
            foreach( $v2ProgramMerchants as $v2ProgramMerchant) {
                if( !$v2ProgramMerchant->v3_merchant_id ) {
                    throw new Exception(sprintf("v2merchant:v3_merchant_id found for v2Program:%s. Please run `php artisan v2migrate:merchants` before running program migration for this program.\n\n", $v2Program->account_holder_id));
                }
                if( !$v2ProgramMerchant->v3_program_id ) {
                    throw new Exception("v2program:v3_program_id found. Please run `php artisan v2migrate:programs [ID]` before running this migration.\n\n");
                }
                if ($v3MerchantIDs[$v2ProgramMerchant->v3_merchant_id] ?? FALSE) {
                    $programMerchants[$v2ProgramMerchant->v3_merchant_id] = [
                        'featured' => $v2ProgramMerchant->featured,
                        'cost_to_program' => $v2ProgramMerchant->cost_to_program
                    ];
                }
            }
            if($programMerchants) {
                try {
                    $v3Program = Program::where('account_holder_id', $v3AccountHolderID)->first();
                    $v3Program->merchants()->sync($programMerchants, false);
                    $result = TRUE;
                } catch (\Exception $exception) {
                    throw new Exception("Sync merchants to a program is failed.");
                }
            }
        }

        return $result;
    }
}
