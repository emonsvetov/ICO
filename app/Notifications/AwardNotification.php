<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

use App\Mail\templates\PeerAllocationEmail;
use App\Mail\templates\AwardBadgeEmail;
use App\Mail\templates\PeerAwardEmail;
use App\Mail\templates\AwardEmail;
use App\Mail\templates\BirthdayBadgeEmail;

class AwardNotification extends Notification implements ShouldQueue
{
    use Queueable;

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

        switch($this->data->notificationType)
        {
            case 'PeerAllocation':
                return (new PeerAllocationEmail(
                    $this->data->awardee_first_name,
                    $this->data->awardPoints,
                    $this->data->awardNotificationBody,
                    $this->data->program,
                ))->convertToMailMessage();
            break;
            case 'PeerAward':
                return (new PeerAwardEmail(
                    $this->data->awardee_first_name,
                    $this->data->awarder_first_name,
                    $this->data->awarder_last_name,
                    $this->data->awardPoints,
                    $this->data->availableAwardPoints,
                    $this->data->program
                ))->convertToMailMessage();
            break;
            case 'BadgeAward':
            case 'MilestoneBadge':
                return (new AwardBadgeEmail(
                    $this->data->awardee_first_name,
                    $this->data->eventName,
                    $this->data->program
                ))->convertToMailMessage();
            break;
            case 'BirthdayBadge':
                return (new BirthdayBadgeEmail(
                    $this->data->awardee_first_name,
                    $this->data->awardNotificationBody,
                    $this->data->program
                ))->convertToMailMessage();
            break;
            case 'Award';
            case 'MilestoneAward':
            default:
                return (new AwardEmail(
                    $this->data->awardee_first_name,
                    $this->data->awardPoints,
                    $this->data->awardNotificationBody,
                    $this->data->program
                ))->convertToMailMessage();
            break;
        }
    }

    public function toArray($notifiable)
    {
        $notification = $this->data;
        $notification->program = [
            'id' => $this->data->program->id,
            'name' => $this->data->program->name,
        ];
        return $notification;
    }
}
