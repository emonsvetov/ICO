<?php

namespace App\Jobs\Program;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

use App\Services\Program\TangoOrderService;

class SubmitTangoOrdersJob implements ShouldQueue
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
    public function handle(TangoOrderService $tangoOrderService)
    {
        \Log::info("SubmitTangoOrdersJob starts!");
        $tangoOrderService->submitOrders();
        \Log::info("SubmitTangoOrdersJob ends!");
    }
}
