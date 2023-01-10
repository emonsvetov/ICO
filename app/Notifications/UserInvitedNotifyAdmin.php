<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UserInvitedNotifyAdmin extends Notification
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
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        $address = env("MAIL_FROM_ADDRESS");
        $name = env("MAIL_FROM_NAME");
        return (new MailMessage)
            ->from($address, $name)
            ->line(sprintf('%s has invited new user %s as a %s to the program %s(%d)', $this->sender->name, $this->recepient->name, $this->recepient->roles()->first()->name, $this->program->name, $this->program->id))
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
