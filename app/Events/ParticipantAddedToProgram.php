<?php
namespace App\Events;

use App\Models\Program;
use App\Models\User;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ParticipantAddedToProgram
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $sender;
    public $recepient;
    public $program;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct(User $recepient, Program $program)
    {
        $this->sender = auth()->user();
        $this->recepient = $recepient;
        $this->program = $program;
    }
}
