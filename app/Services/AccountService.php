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

        return (new PostingService)->create($data);
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
        return self::readExpiredTotalForParticipant($program, $user);
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

    public static function readAvailableBalanceForOwner ($program) {
         $account_type = AccountType::ACCOUNT_TYPE_MONIES_DUE_TO_OWNER;
         $journal_event_types = array ();
         return self::readBalance ( $program->account_holder_id, $account_type, $journal_event_types );
    }
    /**
     * This method returns List of Participant Postings ByAccount And Journal Events
     *
     * @param int $account_holder_id
     * @param string $account_type_name
     * @param array|string $journal_event_types
     * @param boolean $is_credit
     * @return EloquentCollection
     */
	public static function readListParticipantPostingsByAccountAndJournalEvents($account_holder_id, $account_type_name, $journal_event_types, $is_credit = 0) {

        $query = DB::table('accounts AS a');

        $query->addSelect(
            DB::raw("distinct posts. *")
        );

        $query->addSelect([
            'p.account_holder_id as program_id',
            'je.parent_journal_event_id'
        ]);

        $query->addSelect(
            DB::raw("posts.posting_amount * posts.qty as posting_amount_total")
        );

        $query->join('account_types AS at', 'at.id', '=', 'a.account_type_id');
        $query->join('postings AS posts', 'posts.account_id', '=', 'a.id');
        $query->join('journal_events AS je', 'je.id', '=', 'posts.journal_event_id');
        $query->join('journal_event_types AS jet', 'jet.id', '=', 'je.journal_event_type_id');
        $query->leftJoin('postings AS program_postings', 'program_postings.journal_event_id', '=', 'je.id');
        $query->join('accounts AS program_accounts', 'program_accounts.id', '=', 'program_postings.account_id');
        $query->join('programs AS p', 'p.account_holder_id', '=', 'program_accounts.account_holder_id');

        $query->where('a.account_holder_id', '=', $account_holder_id);
        $query->where('at.name', '=', $account_type_name);
        $query->where('posts.is_credit', '=', $is_credit);

        if (is_array ( $journal_event_types )) {
			// this not empty check is nested on purpose, otherwise the else gets executed if the array is empty because an empty array is != ""
			if (! empty ( $journal_event_types )) {
                $query->whereIn('jet.type', $journal_event_types);
			}
		} else if ($journal_event_types != "") {
            $query->where('jet.type', '=', $journal_event_types);
		}

        try {
            $result = $query->get();
            return $result;
        } catch (Exception $e) {
            throw new Exception(sprintf('DB query failed for "%s" in line %d', $e->getMessage(), $e->getLine()), 500);
        }

	}
    /**
     * Alias for "readListParticipantPostingsByAccountAndJournalEvents"
     */
    public static function read_list_participant_postings_by_account_and_journal_events($account_holder_id, $account_type_name, $journal_event_types, $is_credit = 0){
        return self::readListParticipantPostingsByAccountAndJournalEvents($account_holder_id, $account_type_name, $journal_event_types, $is_credit);
    }
    /**
     * This method returns count of Journal Events for user, not sure why program is included in the argument; copied from current application
     *
     * @param Program $program
     * @param User $participant
     * @param array $extraArgs
     * @return integer
     */
    public static function readEventHistoryCountByProgramByParticipant(Program $program, User $participant, $extraArgs=[]) {

        $query = DB::table('accounts');

        $query->addSelect(
            DB::raw("count(journal_events.id) AS count")
        );

        $query->join('account_types', 'accounts.account_type_id', '=', 'account_types.id');
        $query->join('users', 'users.account_holder_id', '=', 'accounts.account_holder_id');
        $query->join('postings', 'postings.account_id', '=', 'accounts.id');
        $query->join('journal_events', 'journal_events.id', '=', 'postings.journal_event_id');

        $query->where('users.account_holder_id', '=', $participant->account_holder_id);
        $query->whereIn('account_types.name', ['Points Awarded', 'Monies Awarded']);

		if (isset($extraArgs['onlyAwards']) && $extraArgs['onlyAwards'] == 1 ) {
            $query->whereNotNull('journal_events.event_xml_data_id');
		}

		if(isset($extraArgs['entrataCurrentAcademicYear']) && $extraArgs['entrataCurrentAcademicYear']  == 1){
            $AY = getEntrataAcademicYear();
            $query->where('journal_events.created_at', '>=', $AY);
        }
        try {
            $result = $query->count();
            return $result;
        } catch (Exception $e) {
            throw new Exception(sprintf('DB query failed for "%s" in line %d', $e->getMessage(), $e->getLine()), 500);
        }
	}
    /**
     * Alias for "readEventHistoryCountByProgramByParticipant"
     */
    public static function read_event_history_count_by_program_by_participant(Program $program, User $participant, $extraArgs=[]) {
        return self::readEventHistoryCountByProgramByParticipant($program, $participant, $extraArgs);
    }
    /**
     * This method returns count of Journal Events for user, not sure why program is included in the argument; copied from current application
     *
     * @param Program $program
     * @param User $participant
     * @param array $extraArgs
     * @return integer
     */
    public static function readEventHistoryByProgramByParticipant(Program $program, User $participant, $extraArgs=[]) {

        $query = DB::table('accounts');

        $query->addSelect(
            [
                DB::raw("if(event_xml_data.name is null, journal_event_types.type, event_xml_data.name) AS name"),
                'accounts.id AS account_id',
                'journal_events.id AS journal_event_id',
                'journal_events.event_xml_data_id',
                'event_xml_data.icon',
                DB::raw("if(is_credit = 1, postings.posting_amount, -postings.posting_amount) AS amount"),
                'event_xml_data.award_level_name',
                DB::raw("if(event_xml_data.notes is null, journal_events.notes, event_xml_data.notes) AS notes"),
                'journal_events.created_at',
                'event_xml_data.referrer',
                'event_xml_data.lease_number',
                'event_xml_data.notification_body',
                'event_xml_data.xml',
                'event_xml_data.token',
                'event_xml_data.email_template_id',
            ]
        );

        $query->join('account_types', 'accounts.account_type_id', '=', 'account_types.id');
        $query->join('users', 'users.account_holder_id', '=', 'accounts.account_holder_id');
        $query->join('postings', 'postings.account_id', '=', 'accounts.id');
        $query->join('journal_events', 'journal_events.id', '=', 'postings.journal_event_id');
        $query->join('journal_event_types', 'journal_events.journal_event_type_id', '=', 'journal_event_types.id');
        $query->join('event_xml_data', 'event_xml_data.id', '=', 'journal_events.event_xml_data_id');

        $query->where('users.account_holder_id', '=', $participant->account_holder_id);
        $query->whereIn('account_types.name', ['Points Awarded', 'Monies Awarded']);

		if (isset($extraArgs['onlyAwards']) && $extraArgs['onlyAwards'] == 1 ) {
            $query->whereNotNull('journal_events.event_xml_data_id');
		}

		if(isset($extraArgs['entrataCurrentAcademicYear']) && $extraArgs['entrataCurrentAcademicYear']  == 1){
            $AY = getEntrataAcademicYear();
            $query->where('journal_events.created_at', '>=', $AY);
        }
        try {
            $result = $query->get();
            return $result;
        } catch (Exception $e) {
            throw new Exception(sprintf('DB query failed for "%s" in line %d', $e->getMessage(), $e->getLine()), 500);
        }
	}
    /**
     * Alias for "readEventHistoryByProgramByParticipant"
     */
    public static function read_event_history_by_program_by_participant(Program $program, User $participant, $extraArgs=[]) {
        return self::readEventHistoryByProgramByParticipant($program, $participant, $extraArgs);
    }
    /**
     * This method returns list of event awards for an user in a program
     *
     * @param Program $program
     * @param User $user
     * @return integer
     */
	public function readListEventAwardsForParticipant(Program $program, User $participant) {
        $factor = (int) $program->factor_valuation;
		if ($program->programIsInvoiceForAwards()) {
			$account_type = AccountType::ACCOUNT_TYPE_POINTS_AWARDED;
		} else {
			$account_type = AccountType::ACCOUNT_TYPE_MONIES_AWARDED;
		}
        DB::statement("SET SQL_MODE=''"); //Fix for groupBy error!
        $query = DB::table('accounts');

        $query->addSelect(
            [
                'event_xml_data.name',
                DB::raw('SUM(postings.posting_amount) AS amount'),
                'postings.journal_event_id AS journal_event_id',
                DB::raw("(postings.posting_amount * {$factor}) AS points"),
            ]
        );
        $query->join('account_types', 'account_types.id', '=', 'accounts.account_type_id');
        $query->join('postings', 'postings.account_id', '=', 'accounts.id');
        $query->join('journal_events', 'journal_events.id', '=', 'postings.journal_event_id');
        $query->join('event_xml_data', 'journal_events.event_xml_data_id', '=', 'event_xml_data.id');

        $query->where('account_types.name', '=', $account_type);
        $query->where('postings.is_credit', '=', 1);
        $query->where('account_holder_id', '=', $participant->account_holder_id);
        $query->groupBy('event_xml_data.name');

        try {
            $result = $query->get();
            return $result;
        } catch (Exception $e) {
            throw new Exception(sprintf('DB query failed for "%s" in line %d', $e->getMessage(), $e->getLine()), 500);
        }
	}
    /**
     * Alias for "readListEventAwardsForParticipant"
     */
    public static function read_list_event_awards_for_participant(Program $program, User $participant, $extraArgs=[]) {
        return self::readListEventAwardsForParticipant($program, $participant, $extraArgs);
    }
    /**
     * This method returns list of event awards with Internal Store for an user in a program
     *
     * @param Program $program
     * @param User $user
     * @return integer
     */

	public static function readListEventAwardsWithInternalStoreForParticipant(Program $program, User $participant) {

        if ($program->programIsInvoiceForAwards()) {
			$account_type = AccountType::ACCOUNT_TYPE_INTERNAL_STORE_POINTS;
		} else {
			$account_type = 'none'; //as in old app
		}

        DB::statement("SET SQL_MODE=''"); //Fix for groupBy error!
        $query = DB::table('accounts');

        $query->addSelect(
            [
                'event_xml_data.name',
                DB::raw('SUM(postings.posting_amount) AS amount'),
                'postings.journal_event_id AS journal_event_id'
            ]
        );

        $query->join('account_types', 'account_types.id', '=', 'accounts.account_type_id');
        $query->join('postings', 'postings.account_id', '=', 'accounts.id');
        $query->join('journal_events', 'journal_events.id', '=', 'postings.journal_event_id');
        $query->join('event_xml_data', 'journal_events.event_xml_data_id', '=', 'event_xml_data.id');

        $query->where('account_types.name', '=', $account_type);
        $query->where('postings.is_credit', '=', 1);
        $query->where('account_holder_id', '=', $participant->account_holder_id);
        $query->groupBy('event_xml_data.name');

        try {
            $result = $query->get();
            return $result;
        } catch (Exception $e) {
            throw new Exception(sprintf('DB query failed for "%s" in line %d', $e->getMessage(), $e->getLine()), 500);
        }
	}
    /**
     * Alias for "readListEventAwardsWithInternalStoreForParticipant"
     */
    public static function read_list_event_awards_with_internal_store_for_participant(Program $program, User $participant) {
        return self::readListEventAwardsWithInternalStoreForParticipant($program, $participant);
    }

    /**
	 *
	 * @author BCM 20150203
	 *         Returns the total amount this participant has ever been awarded
	 * @param int $program_account_holder_id
	 * @param int $participant_account_holder_id
	 * @throws InvalidArgumentException
	 * @return number */
	public static function read_awarded_total_for_participant(Program $program, User $participant) {
		$journal_event_types = array ();
		$account_type = AccountType::ACCOUNT_TYPE_POINTS_AWARDED;
		if ($program->programIsInvoiceForAwards( $program )) { //TO DO
			// use
			$account_type = AccountType::ACCOUNT_TYPE_POINTS_AWARDED;
			$journal_event_types [] = JournalEventType::JOURNAL_EVENT_TYPES_AWARD_POINTS_TO_RECIPIENT;
		} else {
			// use monies
			$account_type = AccountType::ACCOUNT_TYPE_MONIES_AWARDED;
			$journal_event_types [] = JournalEventType::JOURNAL_EVENT_TYPES_AWARD_MONIES_TO_RECIPIENT;
		}
		// return $this->_read_balance($participant_account_holder_id, $account_type, $journal_event_types);
		return self::_read_sum_credits ($participant->account_holder_id, $account_type, $journal_event_types );

	}

	/**
	 *
	 * @author BCM - 20150203
	 *         This method returns the sum of all credits to the given account, using the given journal event.
	 *         Leave journal events to get all credits to the given account.
	 * @return number */
	private static function _read_sum_credits($account_holder_id, $account_type, $journal_event_types = array()) {
		$credits = JournalEvent::read_sum_postings_by_account_and_journal_events(
            $account_holder_id, $account_type, $journal_event_types, 1
        );
		return ( float ) ($credits->total);

	}

    /**
     * Alias for "read_awarded_total_for_participant"
     */
    public static function readAwardedTotalForUser(Program $program, User $user){
        return self::read_awarded_total_for_participant($program, $user);
    }
    //$A
    public static function readRedeemedTotalPeerPointsForParticipant(
        Program $program,
        User $user //participant
    )
    {
        $journal_event_types = [];
        $account_type = AccountType::ACCOUNT_TYPE_PEER2PEER_POINTS;
        if ($program->programIsInvoiceForAwards()) {
            // use points
            $account_type = AccountType::ACCOUNT_TYPE_PEER2PEER_POINTS;
            $journal_event_types [] = JournalEventType::JOURNAL_EVENT_TYPES_AWARD_POINTS_TO_RECIPIENT;
        } else {
            // use monies
            $account_type = AccountType::ACCOUNT_TYPE_MONIES_AWARDED;
            $journal_event_types [] = JournalEventType::JOURNAL_EVENT_TYPES_AWARD_MONIES_TO_RECIPIENT;
        }
        return self::readSumDebits ( $user->account_holder_id, $account_type, $journal_event_types );
    }
    //$A
    public static function readReclaimedTotalPeerPointsForParticipant(
        Program $program,
        User $user
    )
    {
        $journal_event_types = array ();
		$account_type = AccountType::ACCOUNT_TYPE_PEER2PEER_POINTS;
		if ( $program->programIsInvoiceForAwards(true) ) {
			// use points
			$journal_event_types [] = JournalEventType::JOURNAL_EVENT_TYPES_RECLAIM_PEER_POINTS;
		} else {
            throw new Exception('Usupported condition. Contact Administrator', 500);
		}
        return self::readSumDebits ( $user->account_holder_id, $account_type, $journal_event_types );
    }
}
