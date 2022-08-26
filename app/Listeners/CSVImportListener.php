<?php

namespace App\Listeners;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

use App\Events\CSVImportAlert;
use App\Notifications\CSVImportNotification;

class CSVImportListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  object  \App\Events\CSVImportAlert  $event
     * @return void
     */
    public function handle(CSVImportAlert $event)
    {
        //
    }
}
