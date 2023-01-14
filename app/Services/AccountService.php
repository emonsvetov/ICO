<?php

namespace App\Services;

use App\Http\Requests\AccountPostingRequest;
use App\Models\Account;
use App\Models\AccountType;
use App\Models\JournalEvent;
use App\Models\JournalEventType;
use App\Models\Program;
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
     * This method returns the total amount that has ever expired for this participant
     *
     * @param int $account_holder_id
     * @param Program $program
     * @return float
     */
    public function readExpiredBalance(int $account_holder_id, Program $program): float
    {
        $journal_event_types = [];
        if ($program->programIsInvoiceForAwards()) {
            $account_type = AccountType::ACCOUNT_TYPE_POINTS_AWARDED;
            $journal_event_types [] = JournalEventType::JOURNAL_EVENT_TYPES_EXPIRE_POINTS;
            $journal_event_types [] = JournalEventType::JOURNAL_EVENT_TYPES_DEACTIVATE_POINTS;
        } else {
            $account_type = AccountType::ACCOUNT_TYPE_MONIES_AWARDED;
            $journal_event_types [] = JournalEventType::JOURNAL_EVENT_TYPES_EXPIRE_MONIES;
            $journal_event_types [] = JournalEventType::JOURNAL_EVENT_TYPES_DEACTIVATE_MONIES;
        }
        return $this->readSumDebits ( $account_holder_id, $account_type, $journal_event_types );
    }

    /**
     * This method returns the total amount that has ever expired for this participant
     *
     * @param int $account_holder_id
     * @param Program $program
     * @return float
     */
        public function readRedeemedBalance(int $account_holder_id, Program $program): float
    {
        $journal_event_types = [];
        if ($program->programIsInvoiceForAwards()) {
            $account_type = AccountType::ACCOUNT_TYPE_POINTS_AWARDED;
            $journal_event_types [] = JournalEventType::JOURNAL_EVENT_TYPES_REDEEM_POINTS_FOR_GIFT_CODES;
            $journal_event_types [] = JournalEventType::JOURNAL_EVENT_TYPES_REDEEM_POINTS_FOR_INTERNATIONAL_SHOPPING;
        } else {
            $account_type = AccountType::ACCOUNT_TYPE_MONIES_AWARDED;
            $journal_event_types [] = JournalEventType::JOURNAL_EVENT_TYPES_REDEEM_MONIES_FOR_GIFT_CODES;
        }
        return $this->readSumDebits ( $account_holder_id, $account_type, $journal_event_types );
    }

    /**
     * @desc This method returns the sum of all debits to the given account, using the given journal event.
     *       Leave journal events to get all debits to the given account.
     * @param int $account_holder_id
     * @param string $account_type
     * @param array $journal_events
     * @return float
     */
    private function readSumDebits(int $account_holder_id, string $account_type, array $journal_events = []): float
    {
        $debits = JournalEvent::read_sum_postings_by_account_and_journal_events(
            $account_holder_id, $account_type, $journal_events, 0
        );
        return (float)($debits->total);
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
