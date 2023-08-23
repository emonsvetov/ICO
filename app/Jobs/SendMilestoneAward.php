<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Models\Event;

class SendMilestoneAward implements ShouldQueue
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
        echo PHP_EOL . "Sending milestone cron START on " . date('Y-m-d h:i:s') . PHP_EOL;
        try {
        } catch (\Exception $ex) {
            echo " ERROR  " . $ex->getMessage() . PHP_EOL;
        }

        echo "Sending milestone cron ENDED on " . date('Y-m-d h:i:s') . PHP_EOL;
    }
}
