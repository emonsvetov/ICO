<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CheckRabbitMQConsumer extends Command
{
    protected $signature = 'check:rabbitmq-consumer';
    protected $description = 'Check RabbitMQ consumer';

    public function __construct()
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->info('Checking if RabbitMQ consumer is running...');
        $processName = 'artisan rabbitmq:consume';
        exec("pgrep -f '{$processName}'", $output, $return_var);

        if (count($output) === 1) {
            $this->info('Process not found. Starting consumer...');
            $command = "/usr/bin/php artisan rabbitmq:consume > /dev/null 2>&1 &";
            exec($command, $output, $return_var);
            if ($return_var === 0) {
                $this->info('Consumer started successfully.');
            } else {
                $this->error('Failed to start the consumer.');
            }
        } else {
            $this->info('Process is already running.');
        }
    }
}
