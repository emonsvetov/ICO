<?php

namespace App\Mail\templates;

use App\Mail\SendgridEmail;

class CustomAwardEmail extends SendgridEmail
{
    public string $type = 'emails.customAward';
    public $subject = 'You have a new reward!';

    /**
     * Create a new message instance.
     *
     * @return void
     * @throws \Exception
     */
    public function __construct(
        string $contactFirstName,
        string $eventName,
        string $awardNotificationBody,
        string $restrictions,
        $program
    ) {
        parent::__construct();
        $this->init(func_get_args());
    }

}
