<?php

namespace App\Mail\templates;

use App\Mail\SendgridEmail;
use App\Models\Program;

class WelcomeLiveHigh5Email extends SendgridEmail
{
    public string $type = 'emails.welcomeLiveHigh5';
    public $subject = 'Welcome to Live High 5 program!';

    /**
     * Create a new message instance.
     *
     * @return void
     * @throws \Exception
     */
    public function __construct(
        Program $program
    ) {
        parent::__construct();
        $this->init(func_get_args());
    }
}
