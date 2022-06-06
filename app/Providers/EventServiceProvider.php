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
        'App\Events\ProgramCreated' => [
            'App\Listeners\NewOrganizationListner',
        ],
        'App\Events\OrganizationCreated' => [
            'App\Listeners\NewOrganizationListner',
        ],
        // 'App\Events\UserCreated' => [
        //     'App\Listeners\NewUserListner',
        // ],
        // 'App\Events\TangoOrderCreated' => [
        //     'App\Listeners\NewTangoOrderListner',
        // ],
        // 'App\Events\SingleGiftcodeRedeemed' => [
        //     'App\Listeners\SingleGiftcodeRedeemedListner',
        // ],
        // 'App\Events\MultipleGiftcodesRedeemed' => [
        //     'App\Listeners\MultipleGiftcodesRedeemedListner',
        // ],
        // 'App\Events\MerchantDenominationAlert' => [
        //     'App\Listeners\MerchantDenominationAlertListner',
        // ],
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
        return false;
    }
}
