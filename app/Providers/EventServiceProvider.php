<?php

namespace App\Providers;

use Illuminate\Auth\Events\Registered;
use Illuminate\Auth\Listeners\SendEmailVerificationNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Event;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event listener mappings for the application.
     *
     * @var array
     */
    protected $listen = [
        Registered::class => [
            SendEmailVerificationNotification::class,
        ],
        'App\Events\OrganizationCreated' => [
            'App\Listeners\NewOrganizationNotification',
        ],
        'App\Events\UserCreated' => [
            'App\Listeners\NewUserNotification',
        ],
        'App\Events\TangoOrderCreated' => [
            'App\Listeners\NewTangoOrderNotification',
        ],
        'App\Events\SingleGiftcodeRedeemed' => [
            'App\Listeners\SingleGiftcodeRedeemedNotification',
        ],
        'App\Events\MultipleGiftcodesRedeemed' => [
            'App\Listeners\MultipleGiftcodesRedeemedNotification',
        ],
        'App\Events\MerchantDenominationAlert' => [
            'App\Listeners\MerchantDenominationAlertNotification',
        ],
    ];

    /**
     * Register any events for your application.
     *
     * @return void
     */
    public function boot()
    {
        //
    }


    public function shouldDiscoverEvents()
    {
        return true;
    }
}
