<?php

namespace App\Mail\templates;

use App\Mail\SendgridEmail;

class InviteManagerEmail extends SendgridEmail
{
    public string $type = 'emails.inviteManager';
    public $subject = "Request To Set-up Administrator's Account";

    /**
     * Create a new message instance.
     *
     * @return void
     * @throws \Exception
     */
    public function __construct(
        string $contactFirstName,
        string $contactProgramHost0,
        string $contactActivationToken
    ) {
        parent::__construct();
        $this->init(func_get_args());
    }

}
