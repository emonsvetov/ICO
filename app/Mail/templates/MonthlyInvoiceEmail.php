<?php

namespace App\Mail\templates;

use App\Mail\SendgridEmail;

class MonthlyInvoiceEmail extends SendgridEmail
{
    public string $type = 'emails.monthlyInvoice';
    public $subject = 'Incentco Automatic Email Notification';

    /**
     * Create a new message instance.
     *
     * @return void
     * @throws \Exception
     */
    public function __construct(
        string $contactFirstName,
        string $eventName,
        string $contactProgramHost0
    ) {
        parent::__construct();
        $this->init(func_get_args());
    }
}