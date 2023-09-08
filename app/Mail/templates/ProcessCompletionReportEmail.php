<?php

namespace App\Mail\templates;

use App\Mail\SendgridEmail;
use App\Models\Program;

class ProcessCompletionReportEmail extends SendgridEmail
{
    public string $type = 'emails.processCompletionReport';
    public $subject = 'Your file has been processed!';

    /**
     * Create a new message instance.
     *
     * @return void
     * @throws \Exception
     */
    public function __construct(
        string $contactFirstName,
        string $file,
        Program $program
    ) {
        parent::__construct();
        $this->init(func_get_args());
    }

}
