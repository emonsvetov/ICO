<?php

namespace App\Services\Program;

use App\Models\TangoOrdersApi;
use Illuminate\Support\Facades\Log;

class TangoVisaApiService
{

    public function __construct(
    )
    {
        // $this->programService = $programService;
    }

    /**
     * @param $tangoOrdersApi
     * @return \Buildrr\TangoRaasApi\TangoCard
     */
    private function _initTango($tangoOrdersApi)    {
        if(!isset($this->_tangos[$tangoOrdersApi->id])){
            $this->_tangos[$tangoOrdersApi->id] = new \Buildrr\TangoRaasApi\TangoCard(
                $tangoOrdersApi->platform_name,
                $tangoOrdersApi->platform_key
            );
            $this->_tangos[$tangoOrdersApi->id]->setPlatformKey($tangoOrdersApi->platform_key);
            $this->_tangos[$tangoOrdersApi->id]->setPlatformName( $tangoOrdersApi->platform_name);
            $this->_tangos[$tangoOrdersApi->id]->setAppMode($tangoOrdersApi->platform_mode);
        }
        return $this->_tangos[$tangoOrdersApi->id];
    }

    public function submit_order($data, $toaID, $toa_utid=null, $merchant_code = null)
    {
        $tangoOrdersApi = TangoOrdersApi::find($toaID);
        if( !$tangoOrdersApi->exists() ) {
            return [
                'error' => 'Invalid tango order API'
            ];
        }
        $tango = $this->_initTango($tangoOrdersApi);
        if(!$toa_utid){
            $toa_utid = $tangoOrdersApi->udid;
        }


        $data['amount'] = floatval( preg_replace( "/,/", "", $data['amount']));
        $data['amount'] = number_format( $data['amount'], 2, '.', '');

        // $customers = $tango->getOrderList();

        $response = $tango->placeOrder(
            $tangoOrdersApi->customer_number,
            $tangoOrdersApi->account_identifier,
            $data['amount'],
            $toa_utid,
            $tangoOrdersApi->etid,
            $data['sendEmail'],
            isset($data['recipientEmail'])?$data['recipientEmail']:'',
            isset($data['recipientFirstName'])?$data['recipientFirstName']:null,
            isset($data['recipientLastName'])?$data['recipientLastName']:null,
            isset($data['campaign'])?$data['campaign']:null,
            isset($data['emailSubject'])?$data['emailSubject']:null,
            $data['message'],
            $data['notes'],
            isset($data['senderEmail'])?$data['senderEmail']:null,
            isset($data['senderFirstName'])?$data['senderFirstName']:null,
            isset($data['senderLastName'])?$data['senderLastName']:null,
            $data['externalRefID']
        );

        $result = json_decode(json_encode($response->getData()), true);

        $code = '';
        $pin  = '';

        if(isset($result['referenceOrderID']) && $result['referenceOrderID'] && $merchant_code){
            if($merchant_code == 'SLI'){
                $code = $result['reward']['credentials']['PIN'];
            }elseif($merchant_code == 'FLO'){
                $code = $result['reward']['credentials']['Serial Number'];
                $pin = $result['reward']['credentials']['PIN'];
            }else{
                if(isset($result['reward']['credentials']['Redemption Link'])){
                    $code = $result['reward']['credentials']['Redemption Link'];
                }elseif(isset($result['reward']['credentials']['Redemption URL'])){
                    $code = $result['reward']['credentials']['Redemption URL'];
                }elseif(isset($result['reward']['credentials']['Gift Code'])){
                    $code = $result['reward']['credentials']['Gift Code'];
                }elseif(isset($result['reward']['credentials']['E-Gift Card Number'])){
                    $code = $result['reward']['credentials']['E-Gift Card Number'];
                }elseif(isset($result['reward']['credentials']['Card Number'])){
                    $code = $result['reward']['credentials']['Card Number'];
                }else{
                    throw new RuntimeException ('Internal query failed, please contact API administrator', 500 );
                }

                if(isset($result['reward']['credentials']['Security Code'])){
                    $pin = $result['reward']['credentials']['Security Code'];
                }elseif(isset($result['reward']['credentials']['PIN']) && $merchant_code != 'SLI' ){
                    $pin = $result['reward']['credentials']['PIN'];
                }
            }
        }
        $result['code'] = $code;
        $result['pin']  = $pin;

        return $result;
    }
}
