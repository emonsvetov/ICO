<?php

namespace App\Mail\templates;

use App\Mail\SendgridEmail;
use App\Models\EmailTemplate;

class MentionUserEmail extends SendgridEmail
{
    public string $type = 'emails.mention';
    public $subject = 'You are mentioned in social wall!';

    /**
     * Create a new message instance.
     *
     * @return void
     * @throws \Exception
     */
    public function __construct(
        string $name,
        $template,
        string $comment
    ) {
        parent::__construct();
        $this->init(func_get_args());
    }

}
