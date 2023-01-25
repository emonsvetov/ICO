<?php

namespace App\Mail\templates;

use App\Mail\SendgridEmail;
use App\Models\Program;

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
        string $contactActivationToken,
        $program
    ) {
        parent::__construct();
        $this->init(func_get_args());
        $programTemplate = $program->load('template');
        $this->data['template'] =$programTemplate['template'];
    }

}
