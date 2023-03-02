<?php

namespace App\Notifications;

use Illuminate\Notifications\Notification;

use App\Mail\templates\PasswordResetEmail;

class ResetPasswordNotification extends Notification
{

    public $token;
    public $first_name;
    public $program;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct(string $token, string $first_name)
    {
        $this->program = app()->call('App\Services\DomainService@getProgram');
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
        return (new PasswordResetEmail(
                $this->first_name,
                $this->token,
                $this->program
            )
        )->convertToMailMessage();
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