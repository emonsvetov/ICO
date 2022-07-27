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
        string $contactProgramHost0
    ) {
        parent::__construct();
        $this->init(func_get_args());
    }

}
