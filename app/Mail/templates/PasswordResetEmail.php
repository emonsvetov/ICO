<?php

namespace App\Mail\templates;

use App\Mail\SendgridEmail;

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
        string $contactPasswordResetTokenUrl
    ) {
        parent::__construct();
        $this->init(func_get_args());
    }

}
