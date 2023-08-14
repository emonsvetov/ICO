<?php
namespace App\Services\Program\Deposit;

use App\Services\Program\Deposit\DepositHelper;
use App\Models\Program;

abstract class DepositServiceAbstract
{

	const UNKNOWN = 'UNKOWN';
	const UNPAID = 'UNPAID';
	const PAID = 'PAID';
	const DECLINED = 'DECLINED';
	const REFUNDED = 'REFUNDED';

    protected $depositHelper;
    public function __construct()
    {
        $this->depositHelper = new DepositHelper();
    }

    public abstract function init(Program $program, $data);
    public abstract function finalize(Program $program, $data);
}
