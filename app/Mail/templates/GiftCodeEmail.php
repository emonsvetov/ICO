<?php

namespace App\Mail\templates;

use App\Mail\SendgridEmail;

class GiftCodeEmail extends SendgridEmail
{
    public string $type = 'emails.giftCode';
    public $subject = 'You have received a Gift Code';

    /**
     * Create a new message instance.
     *
     * @return void
     * @throws \Exception
     */
    public function __construct(
        string $contactFirstName,
        string $merchantName,
        string $giftCode,
        string $giftCodeUrl,
        float $giftCodeSkuValue,
        int $giftCodePin,
        string $merchantRedemptionInstructions,
        string $contactProgramHost0
    ) {
        parent::__construct();
        $this->init(func_get_args());
    }
}