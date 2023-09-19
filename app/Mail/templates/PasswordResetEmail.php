<?php

namespace App\Mail\templates;

use App\Mail\SendgridEmail;
use App\Models\Program;

class PasswordResetEmail extends SendgridEmail
{
    public string $type = 'emails.passwordReset';
    public $subject = 'Password Reset';

    /**
     * Create a new message instance.
     *
     * @return void
     * @throws \Exception
     */
    public function __construct(
        string $contactFirstName,
        string $passwordResetToken,
        Program $program
    ) {
        parent::__construct();
        $this->init(func_get_args());
    }
}
