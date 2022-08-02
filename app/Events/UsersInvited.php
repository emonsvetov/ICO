<?php
namespace App\Events;

use App\Models\Program;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UsersInvited
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $sender;
    public $recepients;
    public $program;
    public $isResend;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($recepients, Program $program, $isResend = false)
    {
        $this->sender = auth()->user();
        $this->recepients = $recepients;
        $this->program = $program;
        $this->isResend = $isResend;
    }
}