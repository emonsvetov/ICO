<?php

namespace App\Notifications;

use App\Services\DomainService;
use App\Services\HostService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

use App\Mail\templates\InviteParticipantEmail;

class UserInvitedNotifyUser extends Notification
{
    // use Queueable;
    public $sender;
    public $recepient;
    public $token;
    public $program;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($sender, $recepient, $program, $token)
    {
        $this->sender = $sender;
        $this->recepient = $recepient;
        $this->token = $token;
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
     * @param mixed $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     * @throws \Exception
     */
    public function toMail($notifiablem)
    {
        $domainService = new DomainService(new HostService());
        $url = app()->call('App\Services\DomainService@makeUrl'); //This to be removed, URL already set in SendgridEmail.php
        if ($domainService->isAdminAppDomain()){
            $defaultDomain = $this->program->defaultDomain;
            if ($defaultDomain){
                $defaultDomain = $defaultDomain[0];
                $port = $defaultDomain->port;
                $scheme = $defaultDomain->scheme ?? 'http';
                $url =  $scheme . '://' . $defaultDomain->name . ($port ? ':' . $port : '');
            }
        }

        $tokenUrl = $url . '/invitation?token=' . $this->token;
        return (new InviteParticipantEmail($this->recepient->name, $tokenUrl, $this->program))->convertToMailMessage();
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
