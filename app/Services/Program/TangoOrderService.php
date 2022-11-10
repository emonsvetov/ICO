<?php
namespace App\Services\Program;

use App\Models\TangoOrdersApi;
use App\Models\TangoOrder;
use App\Models\Status;

class TangoOrderService
{

    private TangoVisaApiService $tangoVisaApiService;

    public function __construct(TangoVisaApiService $tangoVisaApiService) {
        $this->tangoVisaApiService = $tangoVisaApiService;
    }

    public function submitOrders()  {

        $ordersList = TangoOrder::read_not_submitted_orders();

        $errors = [];

        if($ordersList->isNotEmpty())
        {
            foreach( $ordersList as $tangoOrder )
            {
                $result = $this->submitOrder($tangoOrder);
                // dump($result);
                if( !$result['success'] )
                {
                    $errors[] = array(
                        'program_id' => $tangoOrder->program_id,
                        'physical_order_id' => $tangoOrder->physical_order->id,
                        'ship_to_name' => $tangoOrder->physical_order->ship_to_name,
                        'tango_request_id' => $result['result']['requestId'],
                        'tango_order_log' => $result['result']['debug']
                    );
                }
            }
            dump($errors);
            if( $errors )
            {
                $program_ids = [];
                foreach($errors as $error){
                    $program_ids[] = $error['program_id'];
                }
                $programs = $this->programs_model->read_programs_by_ids($program_ids);
                $programList = convert_array_to_assoc_array($programs, 'account_holder_id', 'name' );
    
                $subject = 'Critical Tango Order Errors';
    
                $htmlMessage  = 'Submission Date: ' . date("Y-m-d H:i:s") . '<br/>';
                $htmlMessage .= 'We found critical Tango submission errors: <br/><br/>';
    
                foreach($errors as $error){
                    $physicalOrderUrl = '/physical-orders/details/' . $error['physical_order_id'];
                    $htmlMessage .= '--------------------------' . '<br/>';
                    $htmlMessage .= 'Program Name: ' . $programList[$error['program_id']] .  '<br/>';
                    $htmlMessage .= 'Program Id: ' . $error['program_id'] .  '<br/>';
                    $htmlMessage .= 'Physical Order ID: <a href="' . base_url($physicalOrderUrl) . '" >' . $error['physical_order_id'] . '</a><br/>';
                    $htmlMessage .= 'Ship to: ' . $error['ship_to_name'] .  '<br/>';
                    $htmlMessage .= 'Tango Request Id: ' . $error['tango_request_id'] .  '<br/>';
                    $htmlMessage .= 'Tango Error Log: ' . $error['tango_order_log'] .  '<br/>';
                    $htmlMessage .= '--------------------------' . '<br/>';
                }
    
                $textBody = str_replace('\n', '<br/>', $htmlMessage);
    
                require_once APPPATH . 'libraries/SesMailer.php';
                $mailAgent = new SesMailer();
                $recipients = array(
                    'emonsvetov@incentco.com',
                    'jhowe@incentco.com',
                    'mbradley@incentco.com'
                );
                $extraArgs = array (
                    'html' => true,
                    'program_id' => $error['program_id'],
                    'email_source' => Log_Email_Sources_Model::TANGO_ORDER_ERROR,
                );
                $mailAgent->sendHTMLEmail($recipients, array(), $textBody, $subject, $htmlMessage, $extraArgs);
            }
        }
        dd("END");
    }

    public function submitOrder(TangoOrder $tangoOrder)
    {
        $error = $result = null;
        try
        {
            if( !$tangoOrder->merchant_id )
            {
                return [
                    'error' => 'No merchant specified for the tango order'
                ];
            }
            if( !$tangoOrder->merchant->use_tango_api )
            {
                return [
                    'error' => 'Merchant is not set to use Tanglo API'
                ];
            }

            $env = config('app.env');
            // $env = 'production';
            $toaID = null;

            $testTangoOrdersApi = TangoOrdersApi::tango_orders_api_get_test();
            if( $env == 'production' )
            {
                //if a Physical Order NOT tied to Demo program then we have to use standard Tango Configuration based on Merchants settings.
                $toaID = $tangoOrder->merchant->toa_id;
                if($tangoOrder->physical_order->program->exists() && $tangoOrder->physical_order->program->is_demo) 
                {
                    $toaID = $testTangoOrdersApi->id;
                }
            }   
            else 
            {
                if( !$testTangoOrdersApi->exists() )    
                {
                    return [
                        'error' => 'Test Tango Configuration does not exist'
                    ];
                }
                $toaID = $testTangoOrdersApi->id;
            }
            if( $toaID )
            {
                if( $tangoOrder->status == TangoOrder::ORDER_STATUS_NEW)
                {
                    $tangoOrder->update([
                        'status' => TangoOrder::ORDER_STATUS_PROCESS
                    ]);

                    $physical_order = $tangoOrder->physical_order;
                    $orderDetails = $physical_order->read_order_details();

                    $notes = (isset($physical_order->notes)) ? json_decode($physical_order->notes, true) : [];

                    if( isset($_SERVER['INCENTCO_USER']) && $_SERVER['INCENTCO_USER'] == 'eugene_local' )
                    {
                        $notes['email'] = 'emonsvetov@incentco.com';
                    } 
                    elseif( isset($_SERVER ['INCENTCO_ENVIRONMENT']) && $_SERVER ['INCENTCO_ENVIRONMENT'] != 'production' ) 
                    {
                            $notes['email'] = 'mbradley@incentco.com';
                    }
                    $data = [
                        'physical_order_id' => $physical_order->id,
                        'amount' => number_format($orderDetails->sku_value, 2),
                        'sendEmail' => isset($notes['email']) ? true : false,
                        'recipientEmail' => isset($notes['email']) ? $notes['email'] : "",
                        'recipientFirstName' => $physical_order->ship_to_name,
                        'recipientLastName' => null,
                        'campaign' => null,
                        'emailSubject' => 'Congratulations on your Reward!',
                        'message' => 'Congratulations on your Reward!',
                        'notes' => 'auto generated order',
                        'senderEmail' => null,
                        'senderFirstName' => null,
                        'senderLastName' => null,
                        'externalRefID' => null
                    ];

                    $result = $this->tangoVisaApiService->submit_order($data, $toaID);

                    $data = [
                        'log'         => json_encode($result),
                        'created_at'  => $result['createdAt']
                    ];

                    if($result['referenceOrderID'])
                    {
                        $data['external_id'] = $result['referenceOrderID'];
                        $data['request_id'] =  null;
                        $data['status'] = TangoOrder::ORDER_STATUS_SUCCESS;
                    }
                    else
                    {
                        $data['external_id'] = null;
                        $data['request_id'] = $result['requestId'];
                        $data['status'] = TangoOrder::ORDER_STATUS_ERROR;
                    }

                    $tangoOrder->update($data);

                    if($data['status'] == TangoOrder::ORDER_STATUS_SUCCESS){
                        $tangoOrder->physical_order->update([
                            'state_type_id' => Status::get_order_shipped_state()
                        ]);
                    }
                    else
                    {
                        throw new \Exception('Order submission failed, Request Id: ' . $result['requestId'] . '; message: ' . $result['message'] );
                    }
                }
                elseif($tangoOrder->status == TangoOrder::ORDER_STATUS_PROCESS)
                {
                    throw new \Exception('Order submission is in process, wait for one minute and try again.' );
                }
                elseif($tangoOrder->status == TangoOrder::ORDER_STATUS_ERROR)
                {
                    throw new \Exception('Order submission failed, open Tango Order card for more details.' );
                }
                elseif($tangoOrder->status == TangoOrder::ORDER_STATUS_SUCCESS)
                {
                    throw new \Exception('Order was successfully submitted, please open Tango Order card for more details.' );
                }
            }
        }
        catch( \Exception $e)
        {
            $error = $e->getMessage();
        }

		return [
		   'result'  => $result,
		   'success' => !$error,
           'error'   => $error
        ];
    }
}