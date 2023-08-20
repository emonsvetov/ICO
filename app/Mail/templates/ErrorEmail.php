<?php

namespace App\Mail\templates;

use App\Mail\SendgridEmail;
use App\Models\Program;

class ErrorEmail extends SendgridEmail
{
    public string $type = 'emails.error';
    public $subject = 'Errors';

    /**
     * Create a new message instance.
     *
     * @return void
     * @throws \Exception
     */
    public function __construct(
        string $subject,
        string $errorMessage
    ) {
        parent::__construct();
        $this->init(func_get_args());
        $this->subject = $subject;
    }
}
