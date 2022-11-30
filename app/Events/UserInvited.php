<?php
namespace App\Events;

use App\Models\Program;
use App\Models\User;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UserInvited
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $sender;
    public $recepient;
    public $program;
    public $token;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $recepient, Program $program, $token)
    {
        $this->sender = auth()->user();
        $this->recepient = $recepient;
        $this->program = $program;
        $this->token = $token;
    }
}