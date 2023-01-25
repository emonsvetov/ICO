<?php

namespace App\Mail\templates;

use App\Mail\SendgridEmail;

class PeerAwardEmail extends SendgridEmail
{
    public string $type = 'emails.peerAward';
    public $subject = 'Peer Award';

    /**
     * Create a new message instance.
     *
     * @return void
     * @throws \Exception
     */
    public function __construct(
        string $contactFirstName,
        string $senderFirstName,
        string $senderLastName,
        int $awardPoints,
        int $availableAwardPoints,
        string $contactProgramHost0,
        $program
    ) {
        parent::__construct();
        $this->init(func_get_args());
    }

}
