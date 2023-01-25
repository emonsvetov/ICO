<?php

namespace App\Mail\templates;

use App\Mail\SendgridEmail;
use App\Models\Program;

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
        string $contactProgramHost0,
        $program
    ) {
        parent::__construct();
        $this->init(func_get_args());
        $programTemplate = $program->load('template');
        $this->data['template'] =$programTemplate['template'];
    }

}
