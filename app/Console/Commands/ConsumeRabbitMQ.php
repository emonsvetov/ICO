<?php

namespace App\Console\Commands;

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
        $this->info('Starting RabbitMQ consumer...');
        $rabbitMQService->consume(function ($msg) use ($rabbitMQService) {
            $transportData = json_decode(base64_decode($msg->body));

            if (isset($transportData->action)) {
                if ($transportData->action == 'redeem_multiple') {
                    $rabbitMQService->markRedeemed($transportData->data);
                    // todo redeem in v3 system
                    //$rabbitMQService->redeemMultiple($transportData->data);
                    $this->info(print_r($transportData,true));
                }
            }
        });
    }
}
