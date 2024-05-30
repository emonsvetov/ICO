<?php

namespace App\Mail\templates;

use App\Models\EmailTemplate;
use App\Mail\SendgridEmail;

class ReferralEmail extends SendgridEmail
{
    public string $type = 'emails.referral';
    public $subject = 'You have a new referral';

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(
        string $contactFirstName,
        string $referrer_first_name,
        string $referrer_last_name,
        ?string $referee_first_name,
        ?string $referee_last_name,
        ?string $referee_email,
        ?string $referee_area_code,
        ?string $referee_phone,
        $program
    )
    {
        parent::__construct();
        $this->init(func_get_args());
    }
}
