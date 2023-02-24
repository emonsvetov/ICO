<?php

namespace App\Mail\templates;

use App\Mail\SendgridEmail;

class AwardBadgeEmail extends SendgridEmail
{
    public string $type = 'emails.awardBadge';
    public $subject = 'Award Badge';

    /**
     * Create a new message instance.
     *
     * @return void
     * @throws \Exception
     */
    public function __construct(
        string $contactFirstName,
        string $eventName,
        $program
    ) {
        parent::__construct();
        $this->init(func_get_args());
    }

}
