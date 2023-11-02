<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewCommentsCreated extends Notification implements ShouldQueue
{
    use Queueable;
    protected $program;
    public $recepient;
    public $comment;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct( $recepient, $comment )
    {
        $this->recepient = $recepient;
        $this->comment = $comment;
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
        return (new MailMessage)
        ->subject('Notification Subject')
        ->line('Hello, this is a notification.');
    }
   
}
