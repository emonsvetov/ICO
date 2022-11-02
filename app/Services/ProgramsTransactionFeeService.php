<?php

namespace App\Services;

use App\Models\Program;
use App\Models\ProgramsTransactionFee;

class ProgramsTransactionFeeService
{
    /**
     * Get the transaction fee that will be applied to the award
     *
     * @param Program $program
     * @param $amount
     * @return float
     */
    public function calculateTransactionFee(Program $program, $amount): float
    {
        $topProgramId = $program->getRoot('id')->id;
        $transactionFee = ProgramsTransactionFee::getByProgramAndAmount((int)$topProgramId, (float)$amount);
        $transactionFee = $transactionFee ? $transactionFee->transaction_fee : 0;
        return (float)$transactionFee;
    }
}
