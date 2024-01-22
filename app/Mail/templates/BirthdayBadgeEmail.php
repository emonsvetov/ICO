<?php

namespace App\Mail\templates;

use App\Mail\SendgridEmail;
use App\Models\EmailTemplate;

class BirthdayBadgeEmail extends SendgridEmail
{
    public string $type = 'emails.birthdayBadge';
    public $subject = 'You have a new reward!';

    /**
     * Create a new message instance.
     *
     * @return void
     * @throws \Exception
     */
    public function __construct(
        string $contactFirstName,
        string $awardNotificationBody,
        $program
    ) {
        parent::__construct();
        $this->init(func_get_args());
    }

}
