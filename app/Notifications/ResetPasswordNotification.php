<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
// use App\Services\DomainService;

use App\Mail\templates\PasswordResetEmail;

class ResetPasswordNotification extends Notification
{
    use Queueable;

    // private DomainService $domainServicestring;
    public $token;
    public $first_name;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(string $token, string $first_name)
    {
        $this->token = $token;
        $this->first_name = $first_name;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $url = app()->call('App\Services\DomainService@makeUrl');
        $url = $url . '/reset-password?token=' . $this->token;

        return (new PasswordResetEmail(
                $this->first_name,
                $url
            )
        );
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}