<?php
namespace App\Services\v2migrate;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Exception;

use App\Http\Requests\GiftcodeRequest;
use App\Models\Merchant;
use App\Models\Giftcode;
use App\Models\JournalEvent;
use App\Models\Posting;
use App\Models\User;

class MigrateGiftcodesService extends MigrationService
{
    public $count = 0;
    public $offset = 0;
    public $limit = 100;
    public $iteration = 0;
    public $cachedv3Merchants = [];
    public $cachedv3Programs = [];
    public $cachedv3Users = [];
    public $cachedv3 = ['Merchant' => [], 'Program' => [], 'User' => []];

    public function getSetCachedV3Model($modelName, $modelId)   {
        if( isset($this->cachedv3[$modelName][$modelId]) ) {
            return $model = $this->cachedv3[$modelName][$modelId];
        }   else {
            $modelClass = "\\App\\Models\\{$modelName}";
            $model = $modelClass::find($modelId);
            if( $model ) {
                $this->cachedv3[$modelName][$modelId] = $model;
                return $model;
            }
        }
    }

    public function __construct()
    {
        parent::__construct();
    }

    /***
     * Migrate v2 giftcodes into v2
     * We will import codes only for imported merchants.
     * Please run "php artisan v2migrate:merchants".
     */
    public function migrate() {
        if( $v3MerchantIds = \App\Models\Merchant::whereNotNull('v2_account_holder_id')->get()->pluck('id') );
        if( !$v3MerchantIds )    {
            //No v2 merchants found. Existing.
            throw new Exception("No v2:merchant found. Aborting migration.\n");
            exit;
        }
        $this->v2db->statement("SET SQL_MODE=''");
        $this->minimalSync(); //This will sync only
        printf("%d codes synched in %d iterations\n", $this->count, $this->iteration);
    }

    /**
     * This function provides a minimal sync between v2 and v3.
     * I call it minimal sync because this is not a user or program specific sync but
     * only a sync of "available codes". Let me explain. In the very first run this
     * function will get all "redemption_date IS NULL" codes and import them into v3
     * and update reference id in both tables. So for next time run we add condition
     * (OR gc.v3_medium_info_id != 0) in the query so that previously syned codes can
     * be looked for "usage" bewteen two syncs and be updated accordingly.
     * NOTE: If an "unused" code was imported from v2 into v3 and in the next run it is
     * found "used" by v2 we delete it from v3 altogether since it had no role in v3 ecosystem.
     * It is possible that it is imported again if we sync user or programs later on but that
     * will be a part of a separate sync (users or programs),
     * hence the "minimalSync".
     */

    public function minimalSync() {
        $this->iteration++;
        $v3ProgramIds = \App\Models\Program::whereNotNull('v2_account_holder_id')->get()->pluck('id');
        $baseSql = "SELECT gc.id, gc.purchase_date, gc.redemption_date, gc.redemption_value,  gc.cost_basis, gc.discount, gc.sku_value, gc.code, gc.pin, gc.redemption_url, gc.v3_medium_info_id, m.v3_merchant_id, p.v3_program_id AS v3_redeemed_program_id, mr.v3_merchant_id AS v3_redeemed_merchant_id, u.v3_user_id FROM `medium_info` gc JOIN `merchants` m ON gc.merchant_account_holder_id=m.account_holder_id LEFT JOIN programs p on p.account_holder_id=gc.redeemed_program_account_holder_id LEFT JOIN users u on u.account_holder_id=gc.redeemed_account_holder_id LEFT JOIN merchants mr ON mr.account_holder_id=gc.redeemed_merchant_account_holder_id WHERE m.v3_merchant_id != 0 AND m.v3_merchant_id IS NOT NULL";
        // pr($v3ProgramIds);
        if( !$v3ProgramIds )    {
            //No v2 programs found. We only migrate unused codes.
            $baseSql .= " AND gc.redemption_date IS NULL";
        }   else {
            //v2 programs found. We import unused codes and used codes which belong to those programs migrated from v2
            $baseSql .= " AND (gc.redemption_date IS NULL OR (gc.redemption_date IS NOT NULL AND p.v3_program_id IN(" . implode(",", $v3ProgramIds->toArray()) .") AND mr.v3_merchant_id IS NOT NULL AND u.v3_user_id IS NOT NULL))";
        }
        $baseSql .= " LIMIT {$this->offset}, {$this->limit}";

        pr($baseSql);
        exit;

        $this->printf("Synce iteration %d started for %d codes\n", $this->iteration, $this->limit);
        $results = $this->v2db->select($baseSql);
        $this->printf("SQL:%s\n", $baseSql);
        $this->printf("%d codes found for syncing\n", count($results));

        // DB::beginTransaction();
        // $this->v2db->beginTransaction();
        try {
            foreach ($results as $row) {
                $createGiftcode = true;
                $v2Updates = [];
                $v3Updates = [];
                if( $row->v3_medium_info_id ) {
                    $this->printf("v2Medium: v3_medium_info_id IS NOT NULL. Confirming.\n");
                    $v3Giftcode = Giftcode::find($row->v3_medium_info_id);
                    if( $v3Giftcode ) {
                        //v3 version of giftcode exists
                        $this->printf("Giftcode exists. Checking for update.\n");
                        $createGiftcode = false;
                        if( !$v3Giftcode->v2_medium_info_id ) {
                            $this->printf("v3Giftcode needs update for v2_medium_info_id.\n");
                            $v3Updates['v2_medium_info_id'] = $row->id;
                            // $this->addV3SQL(sprintf("UPDATE `medium_info` SET `v2_medium_info_id` = '%d' WHERE `id` = '%d'", $row->id, $v3Giftcode->id));
                        }
                    }
                }   else {
                    //still will confirm by the v2:id
                    $this->printf("v2Medium: v3_medium_info_id IS NULL. Confirming.\n");
                    $v3Giftcode = Giftcode::where('v2_medium_info_id', $row->id)->first();
                    if( $v3Giftcode ) {
                        $this->printf("v3Medium found by v2_medium_info_id. Need to update 'v2Medium:v3_medium_info_id'.\n");
                        // $this->addV2SQL(sprintf("UPDATE `medium_info` SET `v3_medium_info_id` = '%d' WHERE `id` = '%d'", $v3Giftcode->id, $row->id, ));
                        $v2Updates['v3_medium_info_id'] = $v3Giftcode->id;
                        $createGiftcode = false;
                    }
                }

                if( $createGiftcode ) {
                    $this->printf("Starting import of code v2:{$row->id} to v3.\n");
                    //This is new code, we need to pull it to v3
                    //Let's first pull v3 merchant
                    $merchant = $this->getSetCachedV3Model("Merchant", $row->v3_merchant_id);
                    if( !$merchant ) throw new Exception("Cannot proceed without v2merchant");

                    $data = [
                        'purchase_date' => $row->purchase_date,
                        'redemption_date' => $row->redemption_date,
                        'redemption_value' => (float) $row->redemption_value,
                        'cost_basis' => (float) $row->cost_basis,
                        'discount' => (float) $row->discount,
                        'sku_value' => (float) $row->sku_value,
                        'code' => $row->code,
                        'pin' => $row->pin,
                        'redemption_url' => $row->redemption_url ? $row->redemption_url : $merchant->website
                    ];

                    $formRequest = new GiftcodeRequest();
                    $validator = Validator::make($data, $formRequest->rules());
                    if ($validator->fails()) {
                        print( $validator->errors()->toJson() . "\n");
                        continue;
                    }
                    $validated = $validator->validated();

                    $response = Giftcode::createGiftcode(
                        User::find(1),
                        $merchant,
                        $validated + ['v2_medium_info_id' => $row->id]
                    );

                    if(isset($response['success'])) {
                        $giftcodeId = $response['gift_code_id'];
                        if(!empty($response['inserted'])) {

                            print("Code imported, v2:{$row->id}=>v3:{$giftcodeId}. \n");

                            $this->count++;

                            $v2Updates['v3_medium_info_id'] = $giftcodeId;
                        }

                        // $this->v2db->statement("UPDATE `medium_info` SET `v3_medium_info_id` = {$newGiftcodeId} WHERE `id` = {$row->id}");
                        if( $giftcodeId ) {
                            $v3Giftcode = Giftcode::find( $giftcodeId );
                        }
                        // $this->addV2SQL("UPDATE `medium_info` SET `v3_medium_info_id` = {$newGiftcodeId} WHERE `id` = {$row->id}");

                        // if( $this->count >= 3) exit;
                    }
                }

                if( !empty($v3Giftcode) ) {
                    //Check for redemption updates
                    if( $row->redemption_date ) {
                        //check redemption_date
                        if( !$v3Giftcode->redemption_date ) {
                            //The code used in v2 but not updated in v3
                            $v3Updates['redemption_date'] = $row->redemption_date;
                        }
                        // check redeemed_program_id
                        if( !$v3Giftcode->redeemed_program_id ) {
                            //The code used in v2 but not updated in v3
                            $v3Updates['redeemed_program_id'] = $row->v3_redeemed_program_id;
                        }
                        // check redeemed_merchant_id
                        if( !$v3Giftcode->redeemed_merchant_id ) {
                            //The code used in v2 but not updated in v3
                            $v3Updates['redeemed_merchant_id'] = $row->v3_redeemed_merchant_id;
                        }
                        // check redeemed_user_id
                        if( !$v3Giftcode->redeemed_user_id ) {
                            //The code used in v2 but not updated in v3
                            $v3Updates['redeemed_user_id'] = $row->v3_redeemed_user_id;
                        }
                    }

                    if( $v3Giftcode->redemption_date ) {
                        //check redemption_date
                        if( !$row->redemption_date ) {
                            //The code used in v3 but not updated in v2
                            $v2Updates['redemption_date'] = $v3Giftcode->redemption_date;
                        }
                        // check redeemed_program_id
                        if( !$row->redeemed_program_account_holder_id ) {
                            //The code used in v3 but not updated in v2
                            $v3RedeemedProgram = $this->getSetCachedV3Model("Program", $row->v3_redeemed_program_id);
                            $v2Updates['redeemed_program_account_holder_id'] = $v3RedeemedProgram->v2_account_holder_id;
                        }
                        // check redeemed_merchant_id
                        if( !$row->redeemed_merchant_account_holder_id ) {
                            //The code used in v2 but not updated in v3
                            $v3RedeemedMerchant = $this->getSetCachedV3Model("Merchant", $row->v3_redeemed_merchant_id);
                            $v2Updates['redeemed_merchant_account_holder_id'] = $v3RedeemedMerchant->v2_account_holder_id;
                        }
                        // check redeemed_user_id
                        if( !$row->redeemed_user_account_holder_id ) {
                            //The code used in v2 but not updated in v3
                            $v3RedeemedUser = $this->getSetCachedV3Model("User", $row->v3_redeemed_user_id);
                            $v2Updates['redeemed_user_account_holder_id'] = $v3RedeemedUser->v2_account_holder_id;
                        }
                    }

                    if( $v3Updates ) {
                        $v3Giftcode->update($v3Updates);
                    }
                    if( $v2Updates ) {
                        $v3Giftcode->update($v3Updates);
                        $v2UpdatePieces = [];
                        foreach($v2Updates as $v2Field => $v2Value) {
                            $v2UpdatePieces[] = "`$v2Field`='$v2Value'";
                        }
                        $this->addV2SQL("UPDATE `medium_info` SET " . implode(',', $v2UpdatePieces) . " WHERE `id` = {$row->id}");
                    }
                }
            }
            // DB::commit();
            // $this->v2db->commit();
            $this->executeV2SQL();
            if( count($results) >= $this->limit) {
                $this->offset = $this->offset + $this->limit;
                // if( $this->count >= 200 ) exit;
                $this->minimalSync();
            }
        } catch(Exception $e)    {
            // DB::rollback();
            // $this->v2db->rollBack();
            throw new Exception("Error migrating merchants. Error:{$e->getMessage()} in Line: {$e->getLine()} in File: {$e->getFile()}\n");
        }
    }

    public function removeGiftcodeFromV3(Giftcode $giftCode) {
        if( $giftCode ) { //If not removed already
            //Get JournalEvents
            $postings = Posting::where('medium_info_id', $giftCode->id)->select('journal_event_id')->get();
            if( $postings ) {
                $journal_event_ids = $postings->pluck('journal_event_id');
                JournalEvent::whereIn('id', $journal_event_ids)->forceDelete();
                print(" --- Removed journal events\n");
                Posting::where('medium_info_id', $giftCode->id)->delete();
                print(" --- Removed postings\n");
            }
            $giftCode->forceDelete();
            print(" -- Removed gift code\n");
        }
    }
}
