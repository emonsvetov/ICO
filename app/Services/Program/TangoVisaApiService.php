<?php

namespace App\Services\Program;

use App\Models\TangoOrdersApi;

class TangoVisaApiService
{

    public function __construct(
    )
    {
        // $this->programService = $programService;
    }

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

    public function submit_order($data, $toaID)
    {
        $tangoOrdersApi = TangoOrdersApi::find($toaID);
        if( !$tangoOrdersApi->exists() ) {
            return [
                'error' => 'Invalid tango order API'
            ];
        }
        $tango = $this->_initTango($tangoOrdersApi);
        $data['amount'] = floatval( preg_replace( "/,/", "", $data['amount']));
        $data['amount'] = number_format( $data['amount'], 2, '.', '');
        // dump($tangoOrdersApi->toArray());
        // $customers = $tango->getOrderList();
        $response = $tango->placeOrder(
            $tangoOrdersApi->customer_number,
            $tangoOrdersApi->account_identifier,
            $data['amount'],
            $tangoOrdersApi->udid,
            $tangoOrdersApi->etid,
            $data['sendEmail'],
            $data['recipientEmail'],
            $data['recipientFirstName'],
            $data['recipientLastName'],
            $data['campaign'],
            $data['emailSubject'],
            $data['message'],
            $data['notes'],
            $data['senderFirstName'],
            $data['senderLastName'],
            $data['externalRefID']
        );
        $result = $response->getData();
        return json_decode(json_encode($result), true);
    }
}
