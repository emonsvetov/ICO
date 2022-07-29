<?php

namespace App\Mail\templates;

use App\Mail\SendgridEmail;

class ActivationReminderEmail extends SendgridEmail
{
    public string $type = 'emails.activationReminder';
    public $subject = "Reminder to Participate in Our Rewards Program";

    /**
     * Create a new message instance.
     *
     * @return void
     * @throws \Exception
     */
    public function __construct(
        string $contactProgramHost0,
        string $contactActivationToken
    ) {
        parent::__construct();
        $this->init(func_get_args());
    }

}
