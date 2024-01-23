<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReferralNotification extends Notification
{
    // use Queueable;
    public $data;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($data)
    {
        $this->data = $data;
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
            ->line(sprintf('A new referral was sent by %s', $this->data->sender_name))
            ->line(sprintf('First name: %s', $this->data->recipient_first_name))
            ->line(sprintf('Last name: %s', $this->data->recipient_last_name))
            ->line(sprintf('email: %s', $this->data->recipient_email))
            ->line(sprintf('area code: %s', $this->data->recipient_area_code))
            ->line(sprintf('phone: %s', $this->data->recipient_phone))
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
        // return $this->data->toArray();
    }
}
