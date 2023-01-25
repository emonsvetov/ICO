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
        string $contactProgramHost0,
        $program
    ) {
        parent::__construct();
        $this->init(func_get_args());
        $programTemplate = $program->load('template');
        $this->data['template'] =$programTemplate['template'];
    }

}
