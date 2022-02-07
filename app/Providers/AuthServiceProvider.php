<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Laravel\Passport\Passport;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use App\Models\Program;
use App\Policies\ProgramPolicy;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\User' => 'App\Policies\UserPolicy',
        'App\Role' => 'App\Policies\RolePolicy',
        'App\Domain' => 'App\Policies\DomainPolicy',
        Program::class => ProgramPolicy::class,
        //'App\Permission' => 'App\Policies\RoleAndPermissionPolicy',
        // 'App\Models\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        if (! $this->app->routesAreCached()) {
            Passport::routes();
        }

        VerifyEmail::toMailUsing(function ($notifiable, $url) {
            $verifyUrl = env('APP_URL', 'http://localhost') . substr($url, strpos($url, "/email/verify/"));

            return (new MailMessage)
                ->subject('Verify Email Address')
                ->line('Click the button below to verify your email address.')
                ->action('Verify Email Address', $verifyUrl);
        });

        /* USE THIS IF YOU WANT AN ADMIN USER
        Gate::before(function (User $user)
                    {
                        if($user->roles->pluck('name')->contains('admin'))
                        {
                            return true;
                        }
                    });
        */
    }
}
