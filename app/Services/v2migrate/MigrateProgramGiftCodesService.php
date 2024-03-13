<?php

namespace App\Services\v2migrate;

use App\Models\Merchant;
use App\Models\Program;
use Illuminate\Support\Facades\Validator;
use Exception;

use App\Http\Requests\GiftcodeRequest;
use App\Models\Giftcode;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class MigrateProgramGiftCodesService extends MigrationService
{
    public array $importedGiftCodes = [];
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

        $this->printf("Starting gift codes migration\n\n",);
        $v2RootPrograms = $this->read_list_all_root_program_ids($programArgs);
        if (!$v2RootPrograms) {
            throw new Exception("No program found. v2AccountHolderID: {$v2AccountHolderID}");
        }

        try {
            $this->migrateGiftCodes($v2RootPrograms);
        } catch(Exception $e) {
            throw new Exception("Error migrating program gift codes. Error:{$e->getMessage()} in Line: {$e->getLine()} in File: {$e->getFile()}");
        }

        return [
            'success' => TRUE,
            'info' => "migrated " . count($this->importedGiftCodes) . " items",
        ];
    }

    /**
     * @param array $v2RootPrograms
     * @return void
     * @throws Exception
     */
    public function migrateGiftCodes(array $v2RootPrograms): void
    {
        foreach ($v2RootPrograms as $v2RootProgram) {
            $this->syncOrCreateGiftCode($v2RootProgram);

            $subPrograms = $this->read_list_children_heirarchy(( int )$v2RootProgram->account_holder_id);
            foreach ($subPrograms as $subProgram) {
                $this->syncOrCreateGiftCode($subProgram);
            }
        }
    }

    public function syncOrCreateGiftCode(object $v2Program)
    {
        $v3Program = Program::findOrFail($v2Program->v3_program_id);

        $this->iteration++;
        $v3ProgramIds = Program::whereNotNull('v2_account_holder_id')->where('id', $v3Program->id)->get()->pluck('id');
        if (!$v3ProgramIds) {
            throw new Exception('No v2 programs found');
        }

        $v2GiftCodes = $this->getProgramGiftCodes($v3ProgramIds->toArray());

        foreach ($v2GiftCodes as $v2GiftCode) {
            $v2Updates = [];
            $v3Updates = [];
            if ($v2GiftCode->redemption_datetime == '0000-00-00 00:00:00') {
                $v2GiftCode->redemption_datetime = null;
            }
            $v3GiftCode = Giftcode::where('v2_medium_info_id', $v2GiftCode->id)->first();
            if ($v3GiftCode) {
                $v2Updates['v3_medium_info_id'] = $v3GiftCode->id;
            }

            if (!$v3GiftCode) {
                $this->printf("Starting import of code v2:{$v2GiftCode->id} to v3.\n");
                $merchant = $this->getSetCachedV3Model("Merchant", $v2GiftCode->v3_merchant_id);
                if (!$merchant) throw new Exception("Cannot proceed without v2merchant");

                $v3GiftCode = $this->createGiftCode($v2GiftCode, $merchant);
                if ($v3GiftCode) {
                    $this->printf("Code imported, v2:{$v2GiftCode->id}=>v3:{$v3GiftCode->id}. \n");
                    $v2Updates['v3_medium_info_id'] = $v3GiftCode->id;
                }
            }

            if (!empty($v3GiftCode)) {
                $this->importedGiftCodes[] = $v3GiftCode->id;
                //Check for redemption updates
                if ($v2GiftCode->redemption_date) {
                    if (!$v2GiftCode->redemption_datetime) {
                        $v2GiftCode->redemption_datetime = $v2GiftCode->redemption_date;
                    }
                    //check redemption_date
                    if (!$v3GiftCode->redemption_date) {
                        //The code used in v2 but not updated in v3
                        $v3Updates['redemption_date'] = $v2GiftCode->redemption_date;
                        $v3Updates['purchased_by_v2'] = 1;
                    }
                    //check redemption_datetime
                    if (!$v3GiftCode->redemption_datetime) {
                        //The code used in v2 but not updated in v3
                        $v3Updates['redemption_datetime'] = $v2GiftCode->redemption_datetime;
                        $v3Updates['purchased_by_v2'] = 1;
                    }
                    // check redeemed_program_id
                    if (!$v3GiftCode->redeemed_program_id) {
                        //The code used in v2 but not updated in v3
                        $v3Updates['redeemed_program_id'] = $v2GiftCode->v3_redeemed_program_id;
                    }
                    // check redeemed_merchant_id
                    if (!$v3GiftCode->redeemed_merchant_id) {
                        //The code used in v2 but not updated in v3
                        $v3Updates['redeemed_merchant_id'] = $v2GiftCode->v3_redeemed_merchant_id;
                    }
                    // check redeemed_user_id
                    if (!$v3GiftCode->redeemed_user_id) {
                        //The code used in v2 but not updated in v3
                        $v3Updates['redeemed_user_id'] = $v2GiftCode->v3_redeemed_user_id;
                    }
                }

                if ($v3GiftCode->redemption_date) {
                    //check redemption_date
                    if (!$v2GiftCode->redemption_date) {
                        //The code used in v3 but not updated in v2
                        $v2Updates['redemption_date'] = $v3GiftCode->redemption_date;
                        $v2Updates['purchased_by_v3'] = 1;
                    }
                    //check redemption_date
                    if (!$v2GiftCode->redemption_datetime) {
                        //The code used in v3 but not updated in v2
                        $v2Updates['redemption_datetime'] = $v3GiftCode->redemption_datetime;
                        $v2Updates['purchased_by_v3'] = 1;
                    }
                    // check redeemed_program_id
                    if (!$v2GiftCode->redeemed_program_account_holder_id) {
                        //The code used in v3 but not updated in v2
                        $v3RedeemedProgram = $this->getSetCachedV3Model("Program", $v2GiftCode->v3_redeemed_program_id);
                        $v2Updates['redeemed_program_account_holder_id'] = $v3RedeemedProgram->v2_account_holder_id;
                    }
                    // check redeemed_merchant_id
                    if (!$v2GiftCode->redeemed_merchant_account_holder_id) {
                        //The code used in v2 but not updated in v3
                        $v3RedeemedMerchant = $this->getSetCachedV3Model("Merchant", $v2GiftCode->v3_redeemed_merchant_id);
                        $v2Updates['redeemed_merchant_account_holder_id'] = $v3RedeemedMerchant->v2_account_holder_id;
                    }
                    // check redeemed_user_id
                    if (!$v2GiftCode->redeemed_user_account_holder_id) {
                        //The code used in v2 but not updated in v3
                        $v3RedeemedUser = $this->getSetCachedV3Model("User", $v2GiftCode->v3_redeemed_user_id);
                        $v2Updates['redeemed_user_account_holder_id'] = $v3RedeemedUser->v2_account_holder_id;
                    }
                }

                if ($v3Updates) {
                    $v3GiftCode->update($v3Updates);
                }
                if ($v2Updates) {
                    $v3GiftCode->update($v3Updates);
                    $v2UpdatePieces = [];
                    foreach ($v2Updates as $v2Field => $v2Value) {
                        $v2UpdatePieces[] = "`$v2Field`='$v2Value'";
                    }
                    $this->addV2SQL("UPDATE `medium_info` SET " . implode(',', $v2UpdatePieces) . " WHERE `id` = {$v2GiftCode->id}");
                }
                $this->count++;
            }
        }
        $this->executeV2SQL();

        if (count($v2GiftCodes) >= $this->limit) {
            $this->offset = $this->offset + $this->limit;
//            $this->syncOrCreateGiftCode();
        }

    }

    /**
     * @throws ValidationException
     * @throws RandomException
     */
    public function createGiftCode(object $v2GiftCode, Merchant $merchant)
    {
        $v3GiftCode = null;
        $code = $this->generateCode();

        // Hard Code:
        if ($v2GiftCode->code) {
            $v3GiftCodeByCode = Giftcode::where('code', $v2GiftCode->code);
            if ($v3GiftCodeByCode) {
                // in the V3 project, the “code” column is unique!
                $code = $v2GiftCode->code . $this->generateCode(10);
            } else {
                $code = $v2GiftCode->code;
            }
        }

        $data = [
            'purchase_date' => $v2GiftCode->purchase_date,
            'redemption_date' => $v2GiftCode->redemption_date,
            'redemption_value' => (float)$v2GiftCode->redemption_value,
            'redemption_datetime' => $v2GiftCode->redemption_datetime,
            'cost_basis' => (float)$v2GiftCode->cost_basis,
            'discount' => (float)$v2GiftCode->discount,
            'sku_value' => (float)$v2GiftCode->sku_value,
            'code' => $code,
            'pin' => $v2GiftCode->pin,
            'redemption_url' => $v2GiftCode->redemption_url ?: $merchant->website,
            'factor_valuation' => $v2GiftCode->factor_valuation
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
            $validated + ['v2_medium_info_id' => $v2GiftCode->id]
        );

        if (isset($response['success'])) {
            $giftCodeId = $response['gift_code_id'] ?? null;
            $v3GiftCode = Giftcode::find($giftCodeId);
        }
        return $v3GiftCode;
    }

    public function generateCode(int $length = 20)
    {
        $code = md5(time());
        if (function_exists('random_bytes')) {
            $code = bin2hex(random_bytes($length));
        }
        if (function_exists('openssl_random_pseudo_bytes')) {
            $code = bin2hex(openssl_random_pseudo_bytes($length));
        }
        return strtoupper($code) . 'XXXX';
    }
}
