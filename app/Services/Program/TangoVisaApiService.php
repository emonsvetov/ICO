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

    public function submit_order($data, $toaID, $toa_utid=null)
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

        //Log::info('code: ' . print_r($tangoOrdersApi, true));


        $data['amount'] = floatval( preg_replace( "/,/", "", $data['amount']));
        $data['amount'] = number_format( $data['amount'], 2, '.', '');
        // dump($tangoOrdersApi->toArray());
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

        $result = $response->getData();
        return json_decode(json_encode($result), true);
    }
}
