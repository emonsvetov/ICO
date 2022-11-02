<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

use App\Mail\MonthlyInvoiceEmail;

class MonthlyInvoiceNotification extends Notification implements ShouldQueue
{
    use Queueable;

    private $toAddress;
    private $attachment;
    private $data;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($toAddress, $attachment, $data)
    {
        $this->toAddress = $toAddress;
        $this->data = $data;
        $this->attachment = $attachment;
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
        return (new MonthlyInvoiceEmail($this->toAddress, $this->attachment, $this->data));
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
