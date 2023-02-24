<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

use App\Mail\templates\PeerAllocationEmail;
use App\Mail\templates\AwardBadgeEmail;
use App\Mail\templates\PeerAwardEmail;
use App\Mail\templates\AwardEmail;

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
        $programUrl = app()->call('App\Services\DomainService@makeUrl');

        Log::info("******* Award Notification *******");
        Log::info($programUrl);
        Log::info($this->data);

        switch($this->data->notificationType)
        {
            case 'PeerAllocation':
                return (new PeerAllocationEmail(
                    $this->data->awardee_first_name, 
                    $this->data->awardPoints, 
                    $this->data->awardNotificationBody, 
                    $this->data->program,
                ));
            break;
            case 'PeerAward':
                return (new PeerAwardEmail(
                    $this->data->awardee_first_name,
                    $this->data->awarder_first_name,
                    $this->data->awarder_last_name, 
                    $this->data->awardPoints, 
                    $this->data->availableAwardPoints, 
                    $programUrl, 
                    $this->data->program
                ));
            break;
            case 'BadgeAward':
                return (new AwardBadgeEmail(
                    $this->data->awardee_first_name, 
                    $programUrl, 
                    $this->data->awardPoints, 
                    $this->data->awardNotificationBody, 
                    $this->data->program
                ));
            break;
            case 'Award';
            default:
                return (new AwardEmail(
                    $this->data->awardee_first_name, 
                    $programUrl, 
                    $this->data->awardPoints, 
                    $this->data->awardNotificationBody, 
                    $this->data->program
                ));
            break;
        }
    }
}