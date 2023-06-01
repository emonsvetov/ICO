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
    public function __construct()
    {
        parent::__construct();
    }

    public function sync() {
        print("Stating codes sync\n");
        $sql = "SELECT gc.*, m.v3_merchant_id FROM `medium_info` gc JOIN `merchants` m ON gc.merchant_account_holder_id=m.account_holder_id WHERE (gc.redemption_date IS NULL OR gc.v3_medium_info_id != 0) AND m.v3_merchant_id != 0 GROUP BY gc.code ORDER BY gc.redemption_date DESC";// We import codes only for imported merchants. Please run merchant import before running this one.
        $results = $this->v2db->select($sql);
        $cachedv3Merchants = [];
        printf("%d codes found for syncing\n", count($results));
        // DB::beginTransaction();
        // $this->v2db->beginTransaction();
        try {
            foreach ($results as $row) {
                if( $row->v3_medium_info_id ) {
                    $giftCode = Giftcode::find($row->v3_medium_info_id);
                    if( !$row->redemption_date ) {
                        if( $giftCode ) { //v3 version of giftcode exists
                            if( $giftCode->redemption_date ) {
                                print("code:{v2:$row->id} purchase/used in {v3:$row->v3_medium_info_id}. Updating v2 version..\n");
                                $this->v2db->statement("UPDATE `medium_info` SET `redemption_date` = '{$giftCode->redemption_date}', `purchased_by_v3` = 1 WHERE `id` = {$row->id}");
                                continue;
                            }
                        }
                        printf("Unused giftcode v2:%d already synched with v3. Skipping..\n", $row->id);
                        continue;
                    } else {
                        print("Found an used code:{v2:$row->id} as {v3:$row->v3_medium_info_id}. Removing code from v3!?\n");
                        //Here "redemption_date" NOT NULL means that this code was previously synched with v3 but now it is marked is redeemed, hence we need to remove it from our current v3 repository of gift codes.
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
                        $this->v2db->statement("UPDATE `medium_info` SET `v3_medium_info_id` = NULL WHERE `id` = {$row->id}");
                        print(" -- updated v2 medium info \"v3_medium_info_id\" to be null\n");
                    }
                    continue;
                }   else {
                    if( $row->redemption_date ) {
                        print("Giftcode used already. Skipping..\n");
                        continue;
                    }
                    print("Starting import of a new and unused code v2:{$row->id} to v3.\n");
                    //This is new code, we need to pull it to v3
                    //Let's first pull v3 merchant
                    if( isset($cachedv3Merchants[$row->v3_merchant_id]) ) {
                        $merchant = $cachedv3Merchants[$row->v3_merchant_id];
                    }   else {
                        $merchant = Merchant::find($row->v3_merchant_id);
                        if( $merchant ) {
                            $cachedv3Merchants[$row->v3_merchant_id] = $merchant;
                        }
                    }

                    if( !$merchant ) continue;

                    $data = [
                        'purchase_date' => $row->purchase_date,
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
                        $newGiftcodeId = $response['gift_code_id'];
                        print("Code imported, v2:{$row->id}=>v3:{$newGiftcodeId}. \n");
                        $this->v2db->statement("UPDATE `medium_info` SET `v3_medium_info_id` = {$newGiftcodeId} WHERE `id` = {$row->id}");
                        $this->count++;
                        // if( $this->count >= 3) exit;
                    }
                }
            }
            // DB::commit();
            // $this->v2db->commit();
        } catch(Exception $e)    {
            // DB::rollback();
            // $this->v2db->rollBack();
            throw new Exception("Error migrating merchants. Error:{$e->getMessage()} in Line: {$e->getLine()} in File: {$e->getFile()}\n");
        }
    }
}
