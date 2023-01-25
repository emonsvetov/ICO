<?php

namespace App\Mail\templates;

use App\Mail\SendgridEmail;

class AwardEmail extends SendgridEmail
{
    public string $type = 'emails.award';
    public $subject = 'You have a new reward!';

    /**
     * Create a new message instance.
     *
     * @return void
     * @throws \Exception
     */
    public function __construct(
        string $contactFirstName,
        string $contactProgramHost0,
        int $awardPoints,
        string $awardNotificationBody,
        $program
    ) {
        parent::__construct();
        $this->init(func_get_args());
//        $programTemplate = $program->load('template');
//        $this->data['template'] =$programTemplate['template'];
    }

}
