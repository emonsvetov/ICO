<?php

namespace App\Mail\templates;

use App\Mail\SendgridEmail;
use App\Models\EmailTemplate;
use App\Models\Program;

class BalanceNotificationEmail extends SendgridEmail
{
    public string $type = 'emails.balanceNotification';
    public $subject = 'Funding Balance Notification';

    /**
     * Create a new message instance.
     *
     * @return void
     * @throws \Exception
     */
    public function __construct(string $content)
    {
        parent::__construct();

        $this->init(func_get_args());
    }
}
