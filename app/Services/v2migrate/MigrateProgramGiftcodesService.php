<?php

namespace App\Services\v2migrate;

use App\Models\Merchant;
use App\Models\Program;
use Illuminate\Support\Facades\Validator;
use Exception;

use App\Http\Requests\GiftcodeRequest;
use App\Models\Giftcode;
use App\Models\JournalEvent;
use App\Models\Posting;
use App\Models\User;

class MigrateProgramGiftcodesService extends MigrationService
{
    public int $count = 0;
    public int $offset = 0;
    public int $limit = 1000;
    public int $iteration = 0;
    public array $cachedV3 = ['Merchant' => [], 'Program' => [], 'User' => []];

    public function getSetCachedV3Model($modelName, $modelId)
    {
        if (isset($this->cachedV3[$modelName][$modelId])) {
            return $this->cachedV3[$modelName][$modelId];
        } else {
            $modelClass = "\\App\\Models\\{$modelName}";
            $model = $modelClass::find($modelId);
            if ($model) {
                $this->cachedV3[$modelName][$modelId] = $model;
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
    public function migrate(object $v2Program, Program $v3Program)
    {
        if ($v3MerchantIds = Merchant::whereNotNull('v2_account_holder_id')->get()->pluck('id'));
        if (!$v3MerchantIds) {
            throw new Exception("No v2:merchant found. Aborting migration.\n");
        }
        $this->v2db->statement("SET SQL_MODE=''");
        $this->minimalSync($v2Program, $v3Program);
        $this->printf("%d codes synched in %d iterations\n", $this->count, $this->iteration);
    }

    public function minimalSync(object $v2Program, Program $v3Program)
    {
        $this->iteration++;
        $v3ProgramIds = Program::whereNotNull('v2_account_holder_id')->where('id', $v3Program->id)->get()->pluck('id');
        $baseSql = "SELECT gc.id, gc.purchase_date, gc.redemption_date, gc.redemption_datetime, gc.redemption_value,  gc.cost_basis, gc.discount, gc.factor_valuation, gc.sku_value, gc.code, gc.pin, gc.redemption_url, gc.v3_medium_info_id, gc.redeemed_program_account_holder_id, gc.redeemed_merchant_account_holder_id, gc.redeemed_account_holder_id AS redeemed_user_account_holder_id, m.v3_merchant_id, p.v3_program_id AS v3_redeemed_program_id, mr.v3_merchant_id AS v3_redeemed_merchant_id, u.v3_user_id AS v3_redeemed_user_id FROM `medium_info` gc JOIN `merchants` m ON gc.merchant_account_holder_id=m.account_holder_id LEFT JOIN programs p on p.account_holder_id=gc.redeemed_program_account_holder_id LEFT JOIN users u on u.account_holder_id=gc.redeemed_account_holder_id LEFT JOIN merchants mr ON mr.account_holder_id=gc.redeemed_merchant_account_holder_id WHERE m.v3_merchant_id != 0 AND m.v3_merchant_id IS NOT NULL";
        if (!$v3ProgramIds) {
            throw new Exception('No v2 programs found. We only migrate unused codes.');
        } else {
            //v2 programs found. We import unused codes and used codes which belong to those programs migrated from v2
            $baseSql .= " AND ((gc.redemption_date IS NOT NULL AND p.v3_program_id IN(" . implode(",", $v3ProgramIds->toArray()) . ") AND mr.v3_merchant_id IS NOT NULL AND u.v3_user_id IS NOT NULL))";
        }
        $baseSql .= " LIMIT {$this->offset}, {$this->limit}";

        $this->printf("Synce iteration %d started for %d codes\n", $this->iteration, $this->limit);
        $results = $this->v2db->select($baseSql);
        $this->printf("SQL:%s\n", $baseSql);
        $this->printf("%d codes found for syncing\n", count($results));

        try {
            foreach ($results as $row) {
                $createGiftcode = true;
                $v2Updates = [];
                $v3Updates = [];
                if ($row->redemption_datetime == '0000-00-00 00:00:00') {
                    $row->redemption_datetime = null;
                }
                if ($row->v3_medium_info_id) {
                    $this->printf("v2Medium: v3_medium_info_id IS NOT NULL. Confirming.\n");
                    $v3Giftcode = Giftcode::find($row->v3_medium_info_id);
                    if ($v3Giftcode) {
                        //v3 version of giftcode exists
                        $this->printf("Giftcode exists. Checking for update.\n");
                        $createGiftcode = false;
                        if (!$v3Giftcode->v2_medium_info_id) {
                            $this->printf("v3Giftcode needs update for v2_medium_info_id.\n");
                            $v3Updates['v2_medium_info_id'] = $row->id;
                        }
                    }
                } else {
                    //still will confirm by the v2:id
                    $this->printf("v2Medium: v3_medium_info_id IS NULL. Confirming.\n");
                    $v3Giftcode = Giftcode::where('v2_medium_info_id', $row->id)->first();
                    if ($v3Giftcode) {
                        $this->printf("v3Medium found by v2_medium_info_id. Need to update 'v2Medium:v3_medium_info_id'.\n");
                        $v2Updates['v3_medium_info_id'] = $v3Giftcode->id;
                        $createGiftcode = false;
                    }
                }

                if ($createGiftcode) {
                    $this->printf("Starting import of code v2:{$row->id} to v3.\n");
                    //This is new code, we need to pull it to v3
                    //Let's first pull v3 merchant
                    $merchant = $this->getSetCachedV3Model("Merchant", $row->v3_merchant_id);
                    if (!$merchant) throw new Exception("Cannot proceed without v2merchant");

                    $length = 20;
                    $code = md5(time());

                    if (function_exists('random_bytes')) {
                        $code = bin2hex(random_bytes($length));
                    }
                    if (function_exists('openssl_random_pseudo_bytes')) {
                        $code = bin2hex(openssl_random_pseudo_bytes($length));
                    }
                    $code = strtoupper($code) . 'XXXX';

                    $data = [
                        'purchase_date' => $row->purchase_date,
                        'redemption_date' => $row->redemption_date,
                        'redemption_value' => (float)$row->redemption_value,
                        'redemption_datetime' => $row->redemption_datetime,
                        'cost_basis' => (float)$row->cost_basis,
                        'discount' => (float)$row->discount,
                        'sku_value' => (float)$row->sku_value,
                        'code' => !empty($row->code) ? $row->code : $code,
                        'pin' => $row->pin,
                        'redemption_url' => $row->redemption_url ? $row->redemption_url : $merchant->website,
                        'factor_valuation' => $row->factor_valuation
                    ];

                    $formRequest = new GiftcodeRequest();
                    $validator = Validator::make($data, $formRequest->rules());
                    if ($validator->fails()) {
                        throw new Exception($validator->errors()->toJson() . "\n");
                    }
                    $validated = $validator->validated();

                    $response = Giftcode::createGiftcode(
                        User::find(1),
                        $merchant,
                        $validated + ['v2_medium_info_id' => $row->id]
                    );


                    if (isset($response['success'])) {
                        $giftcodeId = $response['gift_code_id'];
                        if (!empty($response['inserted'])) {
                            print("Code imported, v2:{$row->id}=>v3:{$giftcodeId}. \n");
                            $v2Updates['v3_medium_info_id'] = $giftcodeId;
                        }

                        if ($giftcodeId) {
                            $v3Giftcode = Giftcode::find($giftcodeId);
                        }
                    }
                }

                if (!empty($v3Giftcode)) {
                    //Check for redemption updates
                    if ($row->redemption_date) {
                        if (!$row->redemption_datetime) {
                            $row->redemption_datetime = $row->redemption_date;
                        }
                        //check redemption_date
                        if (!$v3Giftcode->redemption_date) {
                            //The code used in v2 but not updated in v3
                            $v3Updates['redemption_date'] = $row->redemption_date;
                            $v3Updates['purchased_by_v2'] = 1;
                        }
                        //check redemption_datetime
                        if (!$v3Giftcode->redemption_datetime) {
                            //The code used in v2 but not updated in v3
                            $v3Updates['redemption_datetime'] = $row->redemption_datetime;
                            $v3Updates['purchased_by_v2'] = 1;
                        }
                        // check redeemed_program_id
                        if (!$v3Giftcode->redeemed_program_id) {
                            //The code used in v2 but not updated in v3
                            $v3Updates['redeemed_program_id'] = $row->v3_redeemed_program_id;
                        }
                        // check redeemed_merchant_id
                        if (!$v3Giftcode->redeemed_merchant_id) {
                            //The code used in v2 but not updated in v3
                            $v3Updates['redeemed_merchant_id'] = $row->v3_redeemed_merchant_id;
                        }
                        // check redeemed_user_id
                        if (!$v3Giftcode->redeemed_user_id) {
                            //The code used in v2 but not updated in v3
                            $v3Updates['redeemed_user_id'] = $row->v3_redeemed_user_id;
                        }
                    }

                    if ($v3Giftcode->redemption_date) {
                        //check redemption_date
                        if (!$row->redemption_date) {
                            //The code used in v3 but not updated in v2
                            $v2Updates['redemption_date'] = $v3Giftcode->redemption_date;
                            $v2Updates['purchased_by_v3'] = 1;
                        }
                        //check redemption_date
                        if (!$row->redemption_datetime) {
                            //The code used in v3 but not updated in v2
                            $v2Updates['redemption_datetime'] = $v3Giftcode->redemption_datetime;
                            $v2Updates['purchased_by_v3'] = 1;
                        }
                        // check redeemed_program_id
                        if (!$row->redeemed_program_account_holder_id) {
                            //The code used in v3 but not updated in v2
                            $v3RedeemedProgram = $this->getSetCachedV3Model("Program", $row->v3_redeemed_program_id);
                            $v2Updates['redeemed_program_account_holder_id'] = $v3RedeemedProgram->v2_account_holder_id;
                        }
                        // check redeemed_merchant_id
                        if (!$row->redeemed_merchant_account_holder_id) {
                            //The code used in v2 but not updated in v3
                            $v3RedeemedMerchant = $this->getSetCachedV3Model("Merchant", $row->v3_redeemed_merchant_id);
                            $v2Updates['redeemed_merchant_account_holder_id'] = $v3RedeemedMerchant->v2_account_holder_id;
                        }
                        // check redeemed_user_id
                        if (!$row->redeemed_user_account_holder_id) {
                            //The code used in v2 but not updated in v3
                            $v3RedeemedUser = $this->getSetCachedV3Model("User", $row->v3_redeemed_user_id);
                            $v2Updates['redeemed_user_account_holder_id'] = $v3RedeemedUser->v2_account_holder_id;
                        }
                    }

                    if ($v3Updates) {
                        $v3Giftcode->update($v3Updates);
                    }
                    if ($v2Updates) {
                        $v3Giftcode->update($v3Updates);
                        $v2UpdatePieces = [];
                        foreach ($v2Updates as $v2Field => $v2Value) {
                            $v2UpdatePieces[] = "`$v2Field`='$v2Value'";
                        }
                        $this->addV2SQL("UPDATE `medium_info` SET " . implode(',', $v2UpdatePieces) . " WHERE `id` = {$row->id}");
                    }
                    $this->count++;
                }
            }
            $this->executeV2SQL();
            if (count($results) >= $this->limit) {
                $this->offset = $this->offset + $this->limit;
                $this->minimalSync();
            }
        } catch (Exception $e) {
            throw new Exception("Error migrating merchants. Error:{$e->getMessage()} in Line: {$e->getLine()} in File: {$e->getFile()}\n");
        }
    }
}
