<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Gate;
use Laravel\Passport\Passport;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use App\Incentco;

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
        'App\DomainProgram' => 'App\Policies\DomainProgramPolicy',
        'App\Report' => 'App\Policies\ReportPolicy',
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

        VerifyEmail::toMailUsing(function ($notifiable, $url)
        {
            $verifyUrl = env('APP_URL', 'http://localhost') . substr( $url, strpos($url, "/email/verify/"));

            return (new MailMessage)
                ->subject('Verify Email Address')
                ->line('Click the button below to verify your email address.')
                ->action('Verify Email Address', $verifyUrl);
        });

        // Implicitly grant "Super Admin" role all permissions
        // This works in the app by using gate-related functions like $user->can()
        Gate::before(function ($user, $ability) {
            return $user->hasRole( config('global.super_admin_role_name') ) ? true : null;
            // return true; //remove when permissions + roles are all set
        });
    }
}
