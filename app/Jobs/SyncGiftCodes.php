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
        echo PHP_EOL . "Sync Gift Codes cron is Started on " . date('Y-m-d h:i:s') . PHP_EOL;


        $giftCodes = $this->gift_codes_model->read_not_synced_gift_codes();

        $errors = [];
        foreach($giftCodes as $code){

            $this->gift_codes_model->update_by_id(( int )$code->id,
                ['v3_sync_status'], [Gift_Codes_Model::SYNC_STATUS_IN_PROGRESS]);

            $res = $this->icov3api->purchase_from_v2($code->code);
            if(!$res->success){
                $this->gift_codes_model->update_by_id(( int )$code->id,
                    ['v3_sync_status'], [Gift_Codes_Model::SYNC_STATUS_ERROR]);
                $errors[] = print_r($res, true);
            }else{
                $this->gift_codes_model->update_by_id(( int )$code->id,
                    ['v3_sync_status'], [Gift_Codes_Model::SYNC_STATUS_SUCCESS]);
            }
        }

        if($errors){
            $subject = "V2 Sync Gift Codes with V3 errors";
            $message = print_r($errors, true)."</pre>";
            $to = 'emonsvetov@incentco.com';
            require_once APPPATH.'/libraries/SesMailer.php';
            $mailAgent = new SesMailer();
            $extraArgs = array (
                'html' => true,
                'email_source' => Log_Email_Sources_Model::GIFT_CODE,
            );
            $mailAgent->sendHTMLEmail($to, array(), $message, $subject, $message, $extraArgs);
        }


        $errors = [];

        try {

            $codes = Giftcode::readNotSubmittedTangoCodes();

            foreach( $codes as $code ){

                $data = [
                    'amount' => $code->sku_value,
                    'sendEmail' => false,
                    'message' => 'Congratulations on your Reward!',
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

                if(isset($tangoResult['referenceOrderID']) && $tangoResult['referenceOrderID']){
                    DB::table(MEDIUM_INFO)
                        ->where('id', $code->id)
                        ->update([
                            'code' =>  $tangoResult['code'],
                            'pin' =>  $tangoResult['pin'],
                            'tango_reference_order_id' => $tangoResult['referenceOrderID']
                        ]);
                }else{
                    DB::table(MEDIUM_INFO)
                        ->where('id', $code->id)
                        ->update([
                            'tango_request_id' => $tangoResult['requestId'],
                        ]);
                    $errors[] = array(
                        'code'  => $code,
                        'tango' => $tangoResult
                    );
                }

                echo $code->id . PHP_EOL;
            }

        } catch (\Exception $e) {
            echo " ERROR  " . $e->getMessage() . PHP_EOL;
        }

        if($errors){
            $subject = 'Critical Tango Order Errors V3';

            $htmlMessage  = 'Submission Date: ' . date("Y-m-d H:i:s") . '<br/>';
            $htmlMessage .= 'We found critical Tango submission errors: <br/><br/>';
            $htmlMessage .= 'This message was automatically generated and does not require any response: <br/><br/>';

            foreach($errors as $error){
                $htmlMessage .= '--------------------------' . '<br/>';
                $htmlMessage .= 'Code info: <pre>' . print_r($error['code'], true) .  '</pre><br/>';
                $htmlMessage .= 'Tango Error: <pre>' . print_r($error['tango'],true) .  '</pre><br/>';
                $htmlMessage .= '--------------------------' . '<br/>';
            }

            Mail::to('emonsvetov@incentco.com')->send(new ErrorEmail($subject, $htmlMessage));
        }

        echo PHP_EOL . "END on " . date('Y-m-d h:i:s') . PHP_EOL;
    }
}
