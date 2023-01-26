<?php

namespace App\Mail\templates;

use App\Mail\SendgridEmail;

class RewardExpirationNoticeEmail extends SendgridEmail
{
    public string $type = 'emails.rewardExpirationNotice';
    public $subject = 'Reward Expiration Notice';

    /**
     * Create a new message instance.
     *
     * @return void
     * @throws \Exception
     */
    public function __construct(
        string $contactFirstName,
        int $pointsExpiring,
        string $pointsExpirationDate,
        string $contactProgramHost0,
        $program
    ) {
        parent::__construct();
        $this->init(func_get_args());
    }

}
