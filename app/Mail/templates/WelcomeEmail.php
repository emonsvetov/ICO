<?php

namespace App\Mail\templates;

use App\Mail\SendgridEmail;

class WelcomeEmail extends SendgridEmail
{
    public string $type = 'emails.welcome';
    public $subject = 'Welcome to Our Rewards Program';

    /**
     * Create a new message instance.
     *
     * @return void
     * @throws \Exception
     */
    public function __construct(
        string $contactFirstName,
        string $contactEmail,
        string $contactProgramHost0,
        $program
    ) {
        parent::__construct();
        $this->init(func_get_args());
        $programTemplate = $program->load('template');
        $this->data['template'] =$programTemplate['template'];
    }

}
