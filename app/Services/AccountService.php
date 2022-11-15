<?php

namespace App\Services;

use App\Http\Requests\AccountPostingRequest;
use App\Models\Account;
use App\Models\JournalEvent;
use Illuminate\Support\Facades\Validator;
use Exception;

class AccountService
{

    private PostingService $postingService;

    public function __construct(
        PostingService $postingService
    )
    {
        $this->postingService = $postingService;
    }

    /**
     * This method returns the sum of all credits - all debits to the given account, using the given journal event.
     * Leave journal events to get all credits - all debits to the given account.
     *
     * @param $account_holder_id
     * @param $account_type
     * @param array $journal_event_types
     * @return float
     */
    public function readBalance($account_holder_id, $account_type, array $journal_event_types = []): float
    {
        $credits = JournalEvent::read_sum_postings_by_account_and_journal_events(
            $account_holder_id, $account_type, $journal_event_types, 1
        );
        $debits = JournalEvent::read_sum_postings_by_account_and_journal_events(
            $account_holder_id, $account_type, $journal_event_types, 0
        );
        return (float)(number_format(($credits->total - $debits->total), 2, '.', ''));
    }

    /**
     * @param array $data
     * @return array
     * @throws \Illuminate\Validation\ValidationException
     * @throws Exception
     */
    public function posting(array $data): array
    {
        $formRequest = new AccountPostingRequest();

        $validator = Validator::make($data, $formRequest->rules());

        if ($validator->fails()) {
            throw new Exception($validator->errors()->toJson());
        }

        $validated = (object)$validator->validated();

        $debitAccountId = Account::getIdByColumns([
            'account_holder_id' => $validated->debit_account_holder_id,
            'account_type_id' => $validated->debit_account_type_id,
            'finance_type_id' => $validated->debit_finance_type_id,
            'medium_type_id' => $validated->debit_medium_type_id,
            'currency_type_id' => $validated->currency_type_id
        ]);

        $creditAccountId = Account::getIdByColumns([
            'account_holder_id' => $validated->credit_account_holder_id,
            'account_type_id' => $validated->credit_account_type_id,
            'finance_type_id' => $validated->credit_finance_type_id,
            'medium_type_id' => $validated->credit_medium_type_id,
            'currency_type_id' => $validated->currency_type_id
        ]);

        $data = [
            'journal_event_id' => $validated->journal_event_id,
            'debit_account_id' => $debitAccountId,
            'credit_account_id' => $creditAccountId,
            'posting_amount' => $validated->amount,
            'qty' => 1,
        ];

        return $this->postingService->create($data);
    }
}
