<?php

namespace App\Services\reports;

use Exception;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

class ReportTrialBalanceService extends ReportServiceAbstract
{
    /**
     * Update the trial balance data in the database.
     *
     * @return void
     * @throws Exception
     */
    public function updateTrialBalance(): void
    {
        try {
            DB::table('trial_balance')->truncate();
            $this->insertTrialBalanceData();
        } catch (QueryException $e) {
            throw new Exception("Error updating trial balance: " . $e->getMessage());
        }
    }

    /**
     * Insert data into the trial_balance table.
     *
     * @return void
     */
    private function insertTrialBalanceData(): void
    {
        $query = $this->getBaseQuery();
        $result = DB::statement($query->toSql(), $query->getBindings());

        if (!$result) {
            throw new Exception("Internal query failed, please contact the API administrator");
        }
    }

    /**
     * Build the base query for the trial_balance data insertion.
     *
     * @return Builder
     */
    protected function getBaseQuery(): Builder
    {
        $query = DB::table('postings')
            ->selectRaw("
                postings.id,
                postings.account_id,
                postings.posting_amount,
                postings.qty,
                postings.is_credit
                CASE
                    WHEN EXISTS(SELECT 1 FROM merchants WHERE merchants.account_holder_id = accounts.account_holder_id)
                    THEN 'Merchants'
                    WHEN EXISTS(SELECT 1 FROM programs WHERE programs.account_holder_id = accounts.account_holder_id)
                    THEN 'Programs'
                    WHEN EXISTS(SELECT 1 FROM owners WHERE owners.account_holder_id = accounts.account_holder_id)
                    THEN 'Owners'
                    ELSE 'Recipients'
                END AS account_holder
            ")
            ->join('accounts', 'accounts.id', '=', 'postings.account_id')
            ->join('finance_types', 'finance_types.id', '=', 'accounts.finance_type_id')
            ->join('account_types', 'account_types.id', '=', 'accounts.account_type_id');

        return $query;
    }
}
