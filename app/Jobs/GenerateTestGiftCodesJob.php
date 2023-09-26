<?php

namespace App\Jobs;

use App\Models\Giftcode;
use App\Models\Merchant;
use App\Models\TangoOrdersApi;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\Event;
use App\Notifications\CSVImportNotification;

use DB;

class GenerateTestGiftCodesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct()
    {
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        echo PHP_EOL . "Generating virtual gift cards cron Started on " . date('Y-m-d h:i:s') . PHP_EOL;
        try {

            $denominations = [5, 10, 25, 50, 100, 150, 200];

            $merchants = Merchant::all();

            foreach ($merchants as $merchant){

                $test_codes = (new Giftcode())->read_list_redeemable_denominations_by_merchant((int)$merchant->id,'', false, true);

                echo $merchant->name . ':' . $merchant->account_holder_id . PHP_EOL;

                foreach($denominations as $denomination){

                    $testDenominationCount = 0;
                    foreach($test_codes as $test_code){
                        if($test_code->sku_value == $denomination){
                            $testDenominationCount = $test_code->count;
                            break;
                        }
                    }

                    echo $denomination . ':' . $testDenominationCount . PHP_EOL;

                    if($testDenominationCount < 50){
                        for( $i=$testDenominationCount; $i<50; $i++ ){
                            $discount = (double)$merchant->virtual_discount;

                            $giftcode = [];
                            $giftcode['purchase_date'] = date("m/d/Y");
                            $giftcode['redemption_value'] = $denomination;
                            $giftcode['cost_basis'] = $denomination - ((double)$denomination /100 * $discount);
                            $giftcode['discount'] = $discount;
                            $giftcode['sku_value'] = $denomination;
                            $giftcode['virtual_inventory'] = 0;
                            $giftcode['medium_info_is_test'] = true;

                            $length = 20;
                            if(function_exists('random_bytes')){
                                $code = bin2hex(random_bytes($length));
                            }
                            if(function_exists('mcrypt_create_iv')){
                                $code = bin2hex(mcrypt_create_iv($length, MCRYPT_DEV_URANDOM));
                            }
                            if(function_exists('openssl_random_pseudo_bytes')){
                                $code = bin2hex(openssl_random_pseudo_bytes($length));
                            }

                            $giftcode['code'] = strtoupper($code);
                            $giftcode['redemption_url'] = $merchant->website;

                            if( (int)$merchant->giftcodes_require_pin ){
                                $giftcode['pin'] = rand(1, 9) . rand(1, 9) . rand(1, 9) . rand(1, 9);
                            }

                            $gift_code_response = Giftcode::createGiftcode(null, $merchant, $giftcode);
                            echo $gift_code_response['gift_code_id'] . PHP_EOL;
                        }
                    }
                }
            }
        } catch (\Exception $ex) {
            echo " ERROR  " . $ex->getMessage() . PHP_EOL;
        }

        echo PHP_EOL . "END on " . date('Y-m-d h:i:s') . PHP_EOL;
    }
}
