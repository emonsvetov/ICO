<?php

namespace App\Mail\templates;

use App\Mail\SendgridEmail;

class FeelingSurveyEmail extends SendgridEmail
{
    public string $type = 'emails.feelingSurvey';
    public $subject = 'How are you feeling survey';

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(
        string $first_name,
        string $last_name,
        string $feeling,
        string $email,
        ?string $comment,
        $program
    )
    {
        parent::__construct();
        $this->init(func_get_args());
    }
}
