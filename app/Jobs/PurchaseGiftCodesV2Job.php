<?php

namespace App\Jobs;

use App\Mail\templates\ErrorEmail;
use App\Models\Giftcode;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PurchaseGiftCodesV2Job implements ShouldQueue
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
        if(!env('V2_GIFTCODE_SYNC_ENABLE')){
            echo PHP_EOL . "Purchase Gift Codes process is disabled in the settings" . PHP_EOL;
            exit;
        }

        echo PHP_EOL . "Purchase Gift Codes cron is Started on " . date('Y-m-d h:i:s') . PHP_EOL;

        $Logger = Log::channel('redemption');
        $codes = Giftcode::readNotSyncedCodes();
print_r($codes); die;
        $errors = [];

        foreach( $codes as $code ){
            if($code->v2_sync_status == Giftcode::SYNC_STATUS_ERROR){
                $errors[] = $code->toArray();
            }else{
                DB::table(MEDIUM_INFO)
                    ->where('id', $code->id)
                    ->update(['v2_sync_status' => Giftcode::SYNC_STATUS_IN_PROGRESS]);

                $responseV2 = Http::withHeaders([
                    'X-API-KEY' => env('V2_API_KEY'),
                ])->post(env('V2_API_URL') . '/rest/gift_codes/redeem', [
                    'code' => $code->code,
                    'redeemed_merchant_account_holder_id' => $code->merchant->v2_merchant_id
                ]);

                $Logger->info('V2: ' . $code->code);

                $res = json_decode($responseV2->body());
                if( isset($res->error) && $res->error){
                    $Logger->info('giftcodes_sync result: error. ' . $responseV2->body());
                    DB::table(MEDIUM_INFO)
                        ->where('id', $code->id)
                        ->update(['v2_sync_status' => Giftcode::SYNC_STATUS_ERROR]);
                    $errors[] = print_r($res, true);
                }else{
                    $Logger->info('giftcodes_sync result: success');
                    DB::table(MEDIUM_INFO)
                        ->where('id', $code->id)
                        ->update(['v2_sync_status' => Giftcode::SYNC_STATUS_SUCCESS]);
                }
            }
        }

        if($errors){
            $subject = "V3->V2 Purchase Gift Codes errors";
            $message = "<pre>".print_r($errors, true)."</pre>";
            Mail::to('emonsvetov@incentco.com')->send(new ErrorEmail($subject, $message));
        }
    }
}
