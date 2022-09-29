<?php
namespace App\Services\Statement;

class StatementObject {
    /**
     *
     * @var float $start_balance */
    public $start_balance = 0;

    /**
     *
     * @var float $end_balance */
    public $end_balance = 0;

    /**
     *
     * @var float $invoice_amount */
    public $invoice_amount = 0;

    /**
     *
     * @var string $start_date */
    public $start_date = '';

    /**
     *
     * @var string $end_date */
    public $end_date = '';

    /**
     *
     * @var stdClass[] $debits */
    public $debits = array ();

    /**
     *
     * @var stdClass[] $credits */
    public $credits = array ();

    /**
     *
     * @var string $program_name */
    public $program_name = "";

    /**
     *
     * @var int $program_account_holder_id */
    public $program_account_holder_id = 0;
}