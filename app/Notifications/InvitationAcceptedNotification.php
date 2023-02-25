<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

use App\Mail\templates\WelcomeEmail;

class InvitationAcceptedNotification extends Notification
{
    // use Queueable;
    public $user;
    public $program;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($user, $program = null)
    {
        $this->user = $user;
        $this->program = $program;
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
        if( !$this->program )
        {
            $this->program = app()->call('App\Services\DomainService@getProgram');
        }
        
        return (new WelcomeEmail($this->user->name, $this->user->email, $this->program));
    }
}