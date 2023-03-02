<?php

namespace App\Mail\templates;

use App\Mail\SendgridEmail;
use App\Models\Program;

class InviteParticipantEmail extends SendgridEmail
{
    public string $type = 'emails.inviteParticipant';
    public $subject = "We'd like you to participate in our rewards program";

    /**
     * Create a new message instance.
     *
     * @return void
     * @throws \Exception
     */
    public function __construct(
        string $contactFirstName,
        string $contactActivationTokenUrl,
        Program $program
    ) {
        parent::__construct();
        $this->init(func_get_args());
    }

}
