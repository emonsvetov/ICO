<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserInvitedNotifyUser extends Notification
{
    // use Queueable;
    public $data;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($sender, $recepient, $program)
    {
        $this->sender = $sender;
        $this->recepient = $recepient;
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
        return (new MailMessage)
            ->line(sprintf('Hi %s,', $this->recepient->name))
            ->line(sprintf('You have been invited as a %s', $this->recepient->roles()->first()->name))
            ->line(sprintf('to program %s(%d)', $this->program->name, $this->program->id))
            ->line(sprintf('by %s', $this->sender->name))
            ->line('')
            ->line('In order to accept this invitation, click button ')
            ->action('Go to the App', url('/'))
            ->line('Thank you!');
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
            'sender' => $this->sender->toArray(),
            'recepient' => $this->recepient->toArray(),
            'program' => $this->program->toArray(),
        ];
    }
}