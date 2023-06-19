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

    public function __construct(MerchantService $merchantService, MigrateGiftcodesService $migrateGiftcodesService)
    {
        $this->merchantService = $merchantService;
        $this->migrateGiftcodesService = $migrateGiftcodesService;
        parent::__construct();
    }

    public function migrate() {
        $this->printf("In MigrateMerchantsService.php start migrate\n");
        // $v2Merchants = $this->v2db->select("SELECT * FROM `merchants`");
        $merchant_tree = array ();
        $v2MerchantHierarchy = $this->read_list_hierarchy();
        if( sizeof($v2MerchantHierarchy) > 0) {
            $v2MerchantHierarchy = sort_result_by_rank($merchant_tree, $v2MerchantHierarchy, 'merchant');
        }
        // pr($v2MerchantHierarchy);
        if( $v2MerchantHierarchy ) {
            DB::beginTransaction();
            $this->v2db->beginTransaction();
            try {
                foreach ($v2MerchantHierarchy as $v2MerchantAccountHolderId => $v2MerchantNode) {
                    // pr($v2MerchantAccountHolderId);
                    // pr($v2ListItem);
                    $this->migrateMerchant($v2MerchantNode);
                    // if( $this->importedCount > 3 ) exit;
                }
                // pr($this->programMerchants);
                if( $this->programMerchants ) {
                    $proramIds = array_keys($this->programMerchants);
                    $programs = Program::whereIn('id', $proramIds)->get();
                    foreach($programs as $program) {
                        $this->printf("Syncing \"programs_merchants\" for program:{$program->id}");
                        // pr($this->programMerchants[$program->id]);
                        $program->merchants()->sync($this->programMerchants[$program->id], false);
                    }
                }
                DB::commit();
                $this->v2db->commit();
            }catch(Exception $e)    {
                DB::rollback();
                $this->v2db->rollBack();
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
                exit;
            }
            if( $v2Merchant->v3_merchant_id ) {
                $this->printf("v2Merchant:{$v2Merchant->account_holder_id} exists in v3 as: {$v2Merchant->v3_merchant_id}. Skipping..\n");
                //TODO: update?!
                return;
            }
            $this->printf("Finding Merchant {$v2Merchant->name} in v3\n");
            $v3Merchant = Merchant::where('name', trim($v2Merchant->name))->first();
            if( $v3Merchant ) {
                $this->printf("v2Merchant:{$v2Merchant->account_holder_id} exists in v3 as: {$v2Merchant->v3_merchant_id}. Updating..\n");
                if( !$v3Merchant->v2_account_holder_id ) {
                    $v3Merchant->v2_account_holder_id = $v2Merchant->account_holder_id;
                    $v3Merchant->save();
                }
                if( !$v2Merchant->v3_merchant_id ) {
                    $this->v2db->statement("UPDATE `merchants` SET `v3_merchant_id` = {$v3Merchant->id} WHERE `account_holder_id` = {$v2Merchant->account_holder_id}");
                }
                //TODO: more updates?!
            }   else {
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

                $newMerchant = (new \App\Models\Merchant)->createAccount( $v3MerchantData );
                //Update v2 for reference column
                $this->v2db->statement("UPDATE `merchants` SET `v3_merchant_id` = {$newMerchant->id} WHERE `account_holder_id` = {$v2Merchant->account_holder_id}");

                if( $newMerchant ) {

                    $icons = [];

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
                    $this->importedCount++;
                    $this->printf("New merchant for v2Merchant: {$v2Merchant->account_holder_id} created successfully!\n");

                    $this->readProgramMerchantRelations( $v2Merchant->account_holder_id );
                }
            }
        }
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
                    ORDER BY `" . MERCHANT_PATHS . "`.path_length DESC), `" . MERCHANTS . "`.account_holder_id ) AS rank
            FROM
                merchant_paths
            LEFT JOIN
                `" . MERCHANTS . "` AS ranking_merchant ON `" . MERCHANT_PATHS . "`.ancestor = ranking_merchant.account_holder_id
            WHERE `" . MERCHANT_PATHS . "`.descendant = `" . MERCHANTS . "`.account_holder_id
                ) as rank
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
}
