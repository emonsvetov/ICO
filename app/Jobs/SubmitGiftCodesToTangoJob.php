<?php

namespace App\Jobs;

use App\Mail\SendgridEmail;
use App\Mail\templates\ErrorEmail;
use App\Mail\templates\WelcomeEmail;
use App\Models\Giftcode;
use App\Models\Merchant;
use App\Models\TangoOrdersApi;
use App\Services\Program\TangoVisaApiService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

use App\Models\Event;
use App\Notifications\CSVImportNotification;

class SubmitGiftCodesToTangoJob implements ShouldQueue
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
        echo PHP_EOL . "Resubmit gift codes cron Started on " . date('Y-m-d h:i:s') . PHP_EOL;

        $errors = [];

        try {

            $codes = Giftcode::readNotSubmittedTangoCodes();

            foreach( $codes as $code ){

                $data = [
                    'amount' => $code->sku_value,
                    'sendEmail' => false,
                    'message' => 'C9ongratulations on your Reward!',
                    'notes' => 'auto generated order',
                    'externalRefID' => null
                ];

                $toa_utid = null;
                $denominations = array_map('trim', explode(',', $code->merchant->virtual_denominations));
                foreach($denominations as $denomination){
                    $pieces = array_map('trim', explode(':', $denomination));
                    if(count($pieces) == 3){
                        list($utid, $sku_value, $redemption_value) = $pieces;
                        if($redemption_value == $data['amount']){
                            $toa_utid = $utid;
                            break;
                        }
                    }
                }

                $tangoVisaApiService = new TangoVisaApiService();
                $tangoResult = $tangoVisaApiService->submit_order($data, $code->merchant->toa_id, $toa_utid, $code->merchant->merchant_code);
                if(isset($tangoResult['requestId']) || $tangoResult['referenceOrderID']){
                    if(isset($tangoResult['requestId'])){
                        DB::table(MEDIUM_INFO)
                            ->where('id', $code->id)
                            ->update([
                                'tango_request_id' => $tangoResult['requestId'],
                            ]);
                        $errors[] = array(
                            'code'  => $code,
                            'tango' => $tangoResult
                        );
                    }else{
                        DB::table(MEDIUM_INFO)
                            ->where('id', $code->id)
                            ->update([
                                'code' =>  $tangoResult['code'],
                                'pin' =>  $tangoResult['pin'],
                                'tango_reference_order_id' => $tangoResult['referenceOrderID']
                            ]);
                    }
                }
            }

        } catch (\Exception $e) {
            echo " ERROR  " . $e->getMessage() . PHP_EOL;
        }

        if($errors){
            $subject = 'Critical Tango Order Errors';

            $htmlMessage  = 'Submission Date: ' . date("Y-m-d H:i:s") . '<br/>';
            $htmlMessage .= 'We found critical Tango submission errors: <br/><br/>';
            $htmlMessage .= 'This message was automatically generated and does not require any response: <br/><br/>';

            foreach($errors as $error){
                $htmlMessage .= '--------------------------' . '<br/>';
                $htmlMessage .= 'Code info: ' . print_r($error['code'], true) .  '<br/>';
                $htmlMessage .= 'Tango Error: ' . $error['tango'] .  '<br/>';
                $htmlMessage .= '--------------------------' . '<br/>';
            }
            // emonsvetov@incentco.com
            Mail::to('olegganshonkov@gmail.com')->send(new ErrorEmail($subject, $htmlMessage));
        }

        echo PHP_EOL . "END on " . date('Y-m-d h:i:s') . PHP_EOL;
    }
}
