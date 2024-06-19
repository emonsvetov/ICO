<?php

namespace App\Notifications;

use App\Mail\templates\ReferralEmail;
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
        return (new ReferralEmail(
            $this->data->contactFirstName,
            $this->data->sender_first_name,
            $this->data->sender_last_name,
            $this->data->recipient_first_name,
            $this->data->recipient_last_name,
            $this->data->recipient_email,
            $this->data->recipient_area_code,
            $this->data->recipient_phone,
            $this->data->message,
            $this->data->program,
        ))->convertToMailMessage();
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
