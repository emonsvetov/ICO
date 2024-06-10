<?php

namespace App\Console\Commands;

use AWS\CRT\Log;
use Illuminate\Console\Command;
use App\Services\RabbitMQService;

class ConsumeRabbitMQ extends Command
{
    protected $signature = 'rabbitmq:consume';
    protected $description = 'Rabbitmq consume v3';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle(rabbitMQService $rabbitMQService)
    {
        \Illuminate\Support\Facades\Log::info('1111111');
        $this->info('Starting RabbitMQ consumer...');
        $rabbitMQService->consume(function ($msg) use ($rabbitMQService) {
            $transportData = json_decode(base64_decode($msg->body),true);

            if (isset($transportData['action'])) {
                if ($transportData['action'] == 'redeem_multiple') {
                    $rabbitMQService->markRedeemed($transportData['data']);
                    // todo redeem in v3 system
                    //$rabbitMQService->redeemMultiple($transportData->data);
                    $this->info(print_r($transportData,true));
                }

                if ($transportData['action'] == 'sync_gift_code') {
                    $rabbitMQService->syncGiftCode($transportData['data']);
                    $this->info(print_r($transportData,true));
                }
            }
        });
    }
}
