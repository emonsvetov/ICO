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
use Illuminate\Support\Facades\Mail;

class SyncGiftCodesV2Job implements ShouldQueue
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
            echo PHP_EOL . "Sync Gift Codes process is disabled in the settings" . PHP_EOL;
            exit;
        }

        echo PHP_EOL . "Sync Gift Codes cron is Started on " . date('Y-m-d h:i:s') . PHP_EOL;

        $Logger = Log::channel('redemption');
        $codes = Giftcode::readNotSyncedCodes();
        $errors = [];

        foreach( $codes as $code ){

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
            $Logger->info('giftcodes_sync result:' . $responseV2->body());

            $res = $responseV2->body();
            if(!$res->success){
                DB::table(MEDIUM_INFO)
                    ->where('id', $code->id)
                    ->update(['v2_sync_status' => Giftcode::SYNC_STATUS_ERROR]);
                $errors[] = print_r($res, true);
            }else{
                DB::table(MEDIUM_INFO)
                    ->where('id', $code->id)
                    ->update(['v2_sync_status' => Giftcode::SYNC_STATUS_SUCCESS]);
            }
        }

        if($errors){
            $subject = "V3->V2 Sync Gift Codes errors";
            $message = print_r($errors, true)."</pre>";
            Mail::to('emonsvetov@incentco.com')->send(new ErrorEmail($subject, $message));
        }
    }
}
