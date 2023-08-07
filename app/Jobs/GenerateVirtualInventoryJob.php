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

class GenerateVirtualInventoryJob implements ShouldQueue
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

            $merchants = Merchant::all();

            foreach ($merchants as $merchant){
                if(!$merchant->use_tango_api || !$merchant->use_virtual_inventory){
                    continue;
                }

                $denominations = array_map( 'trim', explode( ',', $merchant->virtual_denominations));

                $extra_args = [];
                if(env('APP_ENV') != 'production'){
                    $extra_args['medium_info_is_test'] = true;
                }

                $real_codes = (new Giftcode())->read_list_redeemable_denominations_by_merchant((int)$merchant->id);

                $virtual_codes = (new Giftcode())->read_list_redeemable_denominations_by_merchant((int)$merchant->id, '', 1);

                echo $merchant->name . ':' . $merchant->account_holder_id . PHP_EOL;

                foreach($denominations as $denomination){

                    $pieces = array_map( 'trim', explode(':', $denomination));
                    if(count($pieces) == 2){
                        list($sku_value, $redemption_value) = $pieces;
                    }elseif(count($pieces) == 3){
                        list($utid, $sku_value, $redemption_value) = $pieces;
                    }else{
                        $sku_value = $redemption_value = $denomination;
                    }

                    echo 'denomination: ' . $denomination . PHP_EOL;
                    echo 'sku_value: ' . $sku_value . PHP_EOL;
                    echo 'redemption_value: ' . $redemption_value . PHP_EOL;

                    $tango = TangoOrdersApi::find($merchant->toa_id);

                    echo 'tango range: ' . $tango->toa_merchant_min_value . ' - ' . $tango->toa_merchant_max_value . PHP_EOL;
                    if($tango->toa_merchant_min_value > $sku_value){
                        continue;
                    }
                    if($tango->toa_merchant_max_value < $sku_value){
                        continue;
                    }

                    $real_code_exist = 0;
                    foreach($real_codes as $real_code){
                        if($real_code->sku_value == $sku_value){
                            $real_code_exist ++;
                        }
                    }

                    if($real_code_exist >= 20){
                        continue;
                    }

                    $existing_virtual_codes = 0;
                    foreach($virtual_codes as $virtual_code){
                        if($virtual_code->sku_value == $sku_value){
                            $existing_virtual_codes ++;
                        }
                    }

                    $amount_codes = $real_code_exist + $existing_virtual_codes;

                    if($amount_codes < 50){
                        for( $i=$amount_codes; $i<50; $i++ ){
                            $discount = (double)$merchant->virtual_discount;

                            $giftcode = [];

                            $giftcode['purchase_date'] = date("Y-m-d");
                            $giftcode['redemption_value'] = $redemption_value;
                            $giftcode['cost_basis'] = $sku_value - ((double)$sku_value /100 * $discount);
                            $giftcode['discount'] = $discount;
                            $giftcode['sku_value'] = $sku_value;
                            $giftcode['virtual_inventory'] = 1;

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
                            echo "<pre>".print_r($gift_code_response, true)."</pre>";
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
