<?php

namespace App\Mail\templates;

use App\Mail\SendgridEmail;

class GoalStatusEmail extends SendgridEmail
{
    public string $type = 'emails.goalStatus';
    public $subject = ' Goal Status';

    /**
     * Create a new message instance.
     *
     * @return void
     * @throws \Exception
     */
    public function __construct(
        string $contactFirstName,
        string $goalName,
        int $goalCurrentProgress,
        int $goalProgress,
        int $goalTarget,
        string $goalEndDate,
        string $contactProgramHost0
    ) {
        parent::__construct();
        $this->init(func_get_args());
    }

}
