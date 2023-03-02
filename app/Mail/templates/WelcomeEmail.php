<?php

namespace App\Mail\templates;

use App\Mail\SendgridEmail;
use App\Models\Program;

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
        Program $program
    ) {
        parent::__construct();
        $this->init(func_get_args());
    }
}
