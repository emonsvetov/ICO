<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

use App\Mail\TangoOrderErrorEmail;

class TangoOrderErrorNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private $toAddress;
    private $data;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($toAddress, $data)
    {
        $this->toAddress = $toAddress;
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
        return (new TangoOrderErrorEmail($this->toAddress, $this->data));
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
    }
}
