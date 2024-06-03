<?php

namespace App\Services;

use App\Models\Merchant;
use App\Models\Program;
use App\Models\User;
use App\Models\UserV2User;
use App\Services\Program\TangoVisaApiService;
use Illuminate\Support\Facades\Log;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitMQService
{
    protected $connection;
    protected $channel;
    protected $exchange;
    protected $queue;

    public function __construct()
    {
        if (env('RABBITMQ_ENABLE')) {
            $this->streamConnection();
        }
    }

    public function streamConnection()
    {
        $this->connection = new AMQPStreamConnection(
            env('RABBITMQ_HOST'),
            env('RABBITMQ_PORT'),
            env('RABBITMQ_USER'),
            env('RABBITMQ_PASSWORD')
        );

        $this->channel = $this->connection->channel();
        $this->exchange = env('RABBITMQ_QUEUE_EXCHANGE');
        $this->queue = env('RABBITMQ_QUEUE');

        $this->channel->exchange_declare($this->exchange, 'topic', false, true, false);
        $this->channel->queue_declare($this->queue, false, true, false, false);
        $this->channel->queue_bind($this->queue, $this->exchange);
    }



    public function publish($routingKey, $message)
    {
        $msg = new AMQPMessage($message);
        $this->channel->basic_publish($msg, $this->exchange, $routingKey);
    }

    public function consume(callable $callback)
    {
        $this->channel->basic_consume($this->queue, '', false, true, false, false, $callback);

        while ($this->channel->is_consuming()) {
            $this->channel->wait();
        }
    }

    public function __destruct()
    {
        if (isset($this->channel)){
            $this->channel->close();
        }

        if (isset($this->connection)){
            $this->connection->close();
        }

    }

    public function redeem($data,$program,$user)
    {
        if ($data['items'] && env('RABBITMQ_ENABLE')) {
            foreach ($data['items'] as $item) {
                $userV2User = UserV2User::where('user_id', $user->id)->first();
                $merchant = Merchant::where('id', $item['merchant_id'])->first();
                $transportData = [
                    'action' => 'redeem_multiple',
                    'data' => [
                        'merchant_account_holder_id' => $merchant->v2_account_holder_id,
                        'account_holder_id' => $userV2User->v2_user_account_holder_id,
                        'program_id' => $program->v2_account_holder_id,
                        'owner_id' => '0',
                        'points_to_redeem' => $item['redemption_value'],
                        'cost_basis' => $item['sku_value'],
                        'discount' => 0,
                        'gift_code_id' => '',
                        'currency_type' => '',
                    ]
                ];
                $transportData = base64_encode(json_encode($transportData));
                $this->publish(env('RABBITMQ_ROUTINGKEY'), $transportData);
            }
        }
    }

    public function redeemMultiple($data)
    {
        $response = [];
        $program = Program::where('v2_account_holder_id', $data->program_id)->first();
        if (!$program) {
            $response['error'][] = 'Program not found';
        }

        $merchant = Merchant::where('v2_account_holder_id', $data->merchant_account_holder_id)->first();
        if (!$merchant) {
            $response['error'][] = 'Merchant not found';
        }

        $userV2User = UserV2User::where('v2_user_account_holder_id', $data->account_holder_id)->first();
        if (!$userV2User) {
            $response['error'][] = 'UserV2User not found';
        }

        if (!isset($response['error']) && !$userV2User->user_id) {
            $response['error'][] = 'User ID not found in UserV2User';
        }

        $user = null;
        if (!isset($response['error'])) {
            $user = User::where('id', $userV2User->user_id)->first();
            if (!$user) {
                $response['error'][] = 'User not found';
            }
        }

        if (!isset($response['error'])) {
            $cart['items'][] = [
                'merchant_id' => $merchant->id,
                'merchant_account_holder_id' => $merchant->account_holder_id,
                'redemption_value' => $data->points_to_redeem,
                'sku_value' => $data->points_to_redeem,
                'virtual_inventory' => 0,
                'redemption_fee' => '0.0000',
                'merchant_name' => $merchant->name,
                'merchant_icon' => $merchant->logo,
                'count' => 1,
                'qty' => 1,
            ];
            $tangoVisaApiService = new TangoVisaApiService();
            $checkoutService = new CheckoutService($tangoVisaApiService);
            $checkoutService = $checkoutService->processOrder($cart, $program, $user);

            Log::info(print_r($checkoutService, true));
            $response['program'] = $checkoutService;
        }

        return $response;
    }

}
