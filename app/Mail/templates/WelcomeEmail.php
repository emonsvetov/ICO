<?php

namespace App\Mail\templates;

use App\Mail\SendgridEmail;
use App\Models\EmailTemplate;
use App\Models\Program;

class WelcomeEmail extends SendgridEmail
{
    public string $type = 'emails.welcome';
    public $subject = 'Welcome to Our Rewards Program';

    /**
     * Create a new message instance.
     *
     * @return void
     * @throws \Exception
     */
    public function __construct(
        string $contactFirstName,
        string $contactEmail,
        Program $program
    ) {
        parent::__construct();

        // Hardcode
        $livhigh5EmailTemplate = EmailTemplate::where('name', 'livhigh5')->first();
        if($livhigh5EmailTemplate){
            $this->type = 'emails.welcome2';
        }

        $this->init(func_get_args());
    }
}
