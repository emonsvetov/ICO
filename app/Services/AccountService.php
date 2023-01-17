<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Exception;

use App\Http\Requests\AccountPostingRequest;
use App\Models\Account;
use App\Models\AccountType;
use App\Models\JournalEvent;
use App\Models\JournalEventType;
use App\Models\Program;
use App\Models\User;

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

    /**
     * This method returns the sum of all credits - all debits to the given account, using the given journal event.
     * Leave journal events to get all credits - all debits to the given account.
     *
     * @param $account_holder_id
     * @param $account_type
     * @param array $journal_event_types
     * @return float
     */
    public static function readBalance($account_holder_id, $account_type, array $journal_event_types = []): float
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
    public function readExpiredBalance(User $user, Program $program): float
    {
        return Account::readExpiredTotalForParticipant($program, $user);
    }

    /**
     * This method returns the total amount that has been redeemed by a participant
     *
     * @param Program $program
     * @param User $user
     * @return float
     */

    /**
     * This method returns the total amount that has been deactivated or expired for a participant
     *
     * @param Program $program
     * @param User $user
     * @return float
     */

    public static function readExpiredTotalForParticipant(
        Program $program, 
        User $user //participant
    ): float
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
        return self::readSumDebits ( $user->account_holder_id, $account_type, $journal_event_types );
    }

    public static function readReclaimedTotalForParticipant(
        Program $program, 
        User $user
    )
    {
        $journal_event_types = array ();
		$account_type = AccountType::ACCOUNT_TYPE_POINTS_AWARDED;
		if ( $program->programIsInvoiceForAwards() ) {
			// use points
			$account_type = AccountType::ACCOUNT_TYPE_POINTS_AWARDED;
			$journal_event_types [] = JournalEventType::JOURNAL_EVENT_TYPES_RECLAIM_POINTS;
			$journal_event_types [] = JournalEventType::JOURNAL_EVENT_TYPES_AWARD_CREDIT_RECLAIM_POINTS;
		} else {
			// use monies
			$account_type = AccountType::ACCOUNT_TYPE_MONIES_AWARDED;
			$journal_event_types [] = JournalEventType::JOURNAL_EVENT_TYPES_RECLAIM_MONIES;
			$journal_event_types [] = JournalEventType::JOURNAL_EVENT_TYPES_AWARD_CREDIT_RECLAIM_MONIES;
		}
        return self::readSumDebits ( $user->account_holder_id, $account_type, $journal_event_types );
    }

    /**
     * Alias for "readReclaimedTotalForParticipant"
     */
    public static function read_reclaimed_total_for_participant(Program $program, User $user)
    {
        return self::readReclaimedTotalForParticipant($program, $user);
    }

    /**
     * This method returns the total amount that has ever expired for this participant
     *
     * @param int $account_holder_id
     * @param Program $program
     * @return float
     */
    public function readRedeemedBalance(User $user, Program $program): float
    {
        return self::readRedeemedTotalForParticipant($program, $user);
    }

    public static function readRedeemedTotalForParticipant(
        Program $program, 
        User $user //participant
    )
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
        return self::readSumDebits ( $user->account_holder_id, $account_type, $journal_event_types );
    }

    public static function readAvailableBalanceForParticipant(
        Program $program, 
        User $user
    )
    {
        $journal_event_types = array (); // leave $journal_event_types empty to get all  - original comment
        if ($program->programIsInvoiceForAwards()) {
			// use points
			$account_type = AccountType::ACCOUNT_TYPE_POINTS_AWARDED;
		} else {
			// use monies
            $account_type = AccountType::ACCOUNT_TYPE_MONIES_AWARDED;
		}
        return self::readBalance ( $user->account_holder_id, $account_type, $journal_event_types );
    }

    /**
     * Alias for "readAvailableBalanceForParticipant"
     */
    public static function readAvailableBalanceForUser(Program $program, User $user){
        return self::readAvailableBalanceForParticipant($program, $user);
    }

    /**
     * @desc This method returns the sum of all debits to the given account, using the given journal event.
     *       Leave journal events to get all debits to the given account.
     * @param int $account_holder_id
     * @param string $account_type
     * @param array $journal_events
     * @return float
     */
    private static function readSumDebits(int $account_holder_id, string $account_type, array $journal_events = []): float
    {
        $debits = JournalEvent::read_sum_postings_by_account_and_journal_events(
            $account_holder_id, $account_type, $journal_events, 0
        );
        return (float)($debits->total);
    }
    /**
     * This method returns available balance for a program
     *
     * @param Program $program
     * @return float
     */

    public static function readAvailableBalanceForProgram( $program ) {
        $account_type = AccountType::ACCOUNT_TYPE_MONIES_AVAILABLE;
		$journal_event_types = array (); // leave $journal_event_types empty to get all journal events
		if ( $program->programIsInvoiceForAwards() ) {
			$account_type = AccountType::ACCOUNT_TYPE_POINTS_AVAILABLE;
		}
		return self::readBalance ( $program->account_holder_id, $account_type, $journal_event_types );
    }
    /**
     * Alias for "readAvailableBalanceForProgram"
     */
    public static function read_available_balance_for_program( $program ) {
		return self::readAvailableBalanceForProgram ( $program );
    }

	public static function readListParticipantPostingsByAccountAndJournalEvents($account_holder_id, $account_type_name, $journal_event_types, $is_credit) {

        $query = DB::table('accounts a');

        $query->addSelect(
            DB::raw("DISTINCT postings.id")
        );

		$sql = "
            select distinct
               posts. *,
               `p`.`account_holder_id` as program_id,
               posts.posting_amount * posts.qty as posting_amount_total,
               je.parent_journal_event_id
            from
                accounts a 
                join account_types at on (at.id = a.account_type_id)
                join postings posts on (posts.account_id = a.id)
                join journal_events je on (je.id = posts.journal_event_id)
                join journal_event_types jet on (jet.id = je.journal_event_type_id)
            LEFT JOIN 
                " . POSTINGS . " `program_posting` ON `program_posting`.`journal_event_id` = je.`id`
            INNER JOIN 
                " . ACCOUNTS . " `program_accounts` ON `program_accounts`.`id` = `program_posting`.`account_id`
            INNER JOIN 
                " . PROGRAMS . " `p` ON `p`.`account_holder_id` = `program_accounts`.`account_holder_id`    
            where
                a.account_holder_id = " . $this->read_db->escape ( ( int ) $account_holder_id ) . "
                and at.account_type_name = " . $this->read_db->escape ( $account_type_name ) . "
                and posts.is_credit = " . $this->read_db->escape ( ( int ) $is_credit ) . "
        ";
		if (is_array ( $journal_event_types )) {
			// this not empty check is nested on purpose, otherwise the else gets executed if the array is empty because an empty array is != ""
			if (! empty ( $journal_event_types )) {
				$sql = $sql . "and jet.type in ('" . implode ( "','", $journal_event_types ) . "')";
			}
		} else if ($journal_event_types != "") {
			$sql = $sql . "and jet.type = " . $this->read_db->escape ( $journal_event_types );
		}
		// throw new RuntimeException($sql);
		$query = $this->read_db->query ( $sql );
		if (! $query) {
			throw new RuntimeException ( $sql . ' Internal query failed, please contact API administrator', 500 );
		}
		return $query->result ();
	
	}

    public static function read_list_participant_postings_by_account_and_journal_events(){
        return self::readListParticipantPostingsByAccountAndJournalEvents();
    }
}
