<?php

namespace App\Services;

use App\Models\AccountType;
use App\Models\Award;
use App\Models\Currency;
use App\Models\Event;
use App\Models\EventType;
use App\Models\FinanceType;
use App\Models\JournalEventType;
use App\Models\MediumType;
use App\Models\Organization;
use App\Models\Program;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class AwardService
{
    private ProgramService $programService;
    private AccountService $accountService;
    private EventXmlDataService $eventXmlDataService;
    private JournalEventService $journalEventService;
    private UserService $userService;

    public function __construct(
        ProgramService $programService,
        EventXmlDataService $eventXmlDataService,
        JournalEventService $journalEventService,
        AccountService $accountService,
        UserService $userService
    ) {
        $this->programService = $programService;
        $this->eventXmlDataService = $eventXmlDataService;
        $this->journalEventService = $journalEventService;
        $this->accountService = $accountService;
        $this->userService = $userService;
    }

    /**
     * @param Program $program
     * @param Organization $organization
     * @param User $currentUser
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function create(Program $program, Organization $organization, User $currentUser, array $data): array
    {
        /** @var Event $event */
        $event = Event::findOrFail($data['event_id']);
        /** @var EventType $eventType */
        $eventType = $event->eventType()->firstOrFail();

        if ($eventType->isEventTypePeer2PeerAllocation()) {
            $newAward = $this->allocatePeer2Peer($program, $currentUser, $data);
        } else {
            if ($eventType->isEventTypePeer2Peer()) {
                $amount = $data['override_cash_value'] ?? 0;
                $users = $data['user_id'] ?? [];
                if ( ! $this->canPeerPayForAwards($program, $currentUser, (float)$amount, $users)) {
                    throw new Exception('Your account balance is too low.');
                }
            }

            $newAward = Award::create(
                (object)($data +
                    [
                        'organization_id' => $organization->id,
                        'program_id' => $program->id
                    ]),
                $program,
                auth()->user()
            );
        }

        return $newAward;
    }

    /**
     * Allocate Peer 2 Peer
     *
     * @param Program $program
     * @param User $currentUser
     * @param array $data
     * @return array
     * @throws Exception
     */
    public function allocatePeer2Peer(Program $program, User $currentUser, array $data): array
    {
        /** @var Event $event */
        $event = Event::findOrFail($data['event_id']);
        /** @var EventType $eventType */
        $eventType = $event->eventType()->firstOrFail();
        $userIds = $data['user_id'] ?? [];
        $overrideCashValue = $data['override_cash_value'] ?? 0;
        $eventAmountOverride = $overrideCashValue > 0;
        $amount = $eventAmountOverride ? $overrideCashValue : $event->max_awardable_amount;
        $amount = (float)$amount;

        if ($this->programService->canProgramPayForAwards($program, $event, $userIds, $amount)) {
            throw new Exception('Your account balance is too low.');
        }

        if ($program->isShellProgram()) {
            throw new InvalidArgumentException ('Invalid program passed, you cannot create an award in a shell program');
        }

        if ( ! $program->uses_peer2peer) {
            throw new InvalidArgumentException ('This program does not allow peer 2 peer allocation');
        }

        foreach ($userIds as $userId) {
            /** @var User $user */
            $user = User::findOrFail($userId);

            if ( ! $user->canBeAwarded($program)) {
                throw new InvalidArgumentException ("User cannot be rewarded. User Id: {$userId}");
            }
        }

        if (in_array($currentUser->id, $userIds)) {
            throw new InvalidArgumentException ('You can\'t award your own account');
        }

        if ( ! $eventType->isEventTypePeer2PeerAllocation()) {
            throw new InvalidArgumentException ("Event type must be peer2peer allocation. Current type: {$event->eventType()->first()->type}");
        }

        $result = [];
        DB::beginTransaction();
        try {
            $users = User::whereIn('id', $userIds)->select(['id', 'account_holder_id'])->get();
            foreach ($users as $user) {
                $userId = $user->id;
                $userAccountHolderId = $user->account_holder_id;

                $data = [
                    'awarder_account_holder_id' => $currentUser->account_holder_id,
                    'name' => $event->name,
                    'award_level_name' => 'Default', // TODO: award_level
                    'amount_override' => $eventAmountOverride,
                    'notification_body' => $data['message'] ?? '',
                    'notes' => $data['notes'] ?? '',
                    'referrer' => $data['referrer'] ?? null,
                    'email_template_id' => $data['email_template_id'] ?? null,
                    'event_type_id' => $event->event_type_id,
                    'event_template_id' => $event->id,
                    'icon' => $event->event_icon_id,
                ];
                $eventXmlDataId = $this->eventXmlDataService->create($data);

                $result[$userId]['event_xml_data_id'] = $eventXmlDataId;
                $result[$userId]['userAccountHolderId'] = $userAccountHolderId;

                $journalEventTypeId = JournalEventType::getTypeAllocatePeerPoints();
                $data = [
                    'journal_event_type_id' => $journalEventTypeId,
                    'event_xml_data_id' => $eventXmlDataId,
                    'notes' => $data['notes'] ?? '',
                    'prime_account_holder_id' => $currentUser->account_holder_id,
                ];
                $journalEventId = $this->journalEventService->create($data);

                $liability = FinanceType::getTypeLiability();
                $points = MediumType::getTypePoints();
                $accountTypePeer2PeerPoints = AccountType::getTypeIdPeer2PeerPoints();
                $currencyId = Currency::getDefault();

                $data = [
                    'debit_account_holder_id' => $program->account_holder_id,
                    'debit_account_type_id' => $accountTypePeer2PeerPoints,
                    'debit_finance_type_id' => $liability,
                    'debit_medium_type_id' => $points,
                    'credit_account_holder_id' => $userAccountHolderId,
                    'credit_account_type_id' => $accountTypePeer2PeerPoints,
                    'credit_finance_type_id' => $liability,
                    'credit_medium_type_id' => $points,
                    'journal_event_id' => $journalEventId,
                    'amount' => $amount,
                    'currency_type_id' => $currencyId,
                ];
                $result[$userId]['recipient_postings'] = $this->accountService->posting($data);
                DB::commit();
            }

        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
        return $result;
    }

    /**
     * @param Program $program
     * @param User $user
     * @param float $amount
     * @param array $users
     * @return bool
     */
    public function canPeerPayForAwards(Program $program, User $user, float $amount, array $users): bool
    {
        $available = $this->userService->readAvailablePeerBalance($user, $program);
        $awardTotal = count($users) * $amount;
        if ($available < $awardTotal) {
            return false;
        }
        return true;
    }

    public function readEventHistoryByProgramAndParticipant(
        int $program_account_holder_id,
        int $participant_account_holder_id,
        int $limit = 0,
        int $offset = 0,
        string $order_column = 'journal_event_timestamp',
        string $order_direction = 'desc',
        $extraArgs = []
    ) {
        $query = DB::table('accounts');
        $query->join('account_types', 'account_types.id', '=', 'accounts.account_type_id');
        $query->join('users', 'users.account_holder_id', '=', 'accounts.account_holder_id');
        $query->join('postings', 'postings.account_id', '=', 'accounts.id');
        $query->join('journal_events', 'journal_events.id', '=', 'postings.journal_event_id');
        $query->join('journal_event_types', 'journal_event_types.id', '=', 'journal_events.journal_event_type_id');
        $query->leftJoin('event_xml_data', 'event_xml_data.id', '=', 'journal_events.event_xml_data_id');

        $query->addSelect(
            DB::raw("
            if(`event_xml_data`.name is null, `journal_event_types`.type, `event_xml_data`.name) AS name,
				`accounts`.id AS account_id,
				`journal_events`.id AS journal_event_id,
				`journal_events`.event_xml_data_id,
				`event_xml_data`.icon,
				if(is_credit = 1, `postings`.posting_amount, -`postings`.posting_amount) AS amount ,
				`event_xml_data`.award_level_name ,
				if(`event_xml_data`.notes is null, `journal_events`.notes, `event_xml_data`.notes) AS notes,
				`journal_events`.created_at as 'journal_event_timestamp',
				`event_xml_data`.referrer,
				`event_xml_data`.lease_number,
				`event_xml_data`.token,
				`event_xml_data`.notification_body,
                `event_xml_data`.xml,
                `event_xml_data`.email_template_id
            ")
        );

        $query->where('users.account_holder_id', '=', $participant_account_holder_id);
        $query->whereIn('account_types.name', [AccountType::ACCOUNT_TYPE_POINTS_AWARDED,AccountType::ACCOUNT_TYPE_MONIES_AWARDED]);
        if (isset($extraArgs['onlyAwards']) && $extraArgs['onlyAwards'] == 1) {
            $query->whereNotNull('event_xml_data.id');
        }
        $query->orderBy($order_column,$order_direction);

        try {
            return [
                'data' => $query->limit($limit)->offset($offset)->get(),
                'total' => $query->count()
            ];
        } catch (Exception $e) {
            throw new Exception('DB query failed.', 500);
        }
    }

    public function readListExpireFuture(Program $program, User $user)
    {
		// $rule = $this->expiration_rules_model->read ( $program->account_holder_id, $program->expiration_rule_id );
		// $end_date_sql = $this->expiration_rules_model->get_embeddable_sql ( $rule, POSTINGS . ".posting_timestamp", null, $program->custom_expire_offset, $program->custom_expire_units, $program->annual_expire_month, $program->annual_expire_day );
        $end_date_sql = '2023-12-31'; //need to get this date TODO

		// build and run the query and store it into the $query variable for
		// later use and validation of the $query object

		$reclaim_jet = [];
		if ( $program->programIsInvoiceForAwards() ) 
        {
		    $account_name = AccountType::ACCOUNT_TYPE_POINTS_AWARDED;
		    $reclaim_jet[] = "Reclaim points";
		    $reclaim_jet[] = "Award credit reclaim points";
		}
        else
        {
		    $account_name = AccountType::ACCOUNT_TYPE_MONIES_AWARDED;
			$reclaim_jet[] = "Reclaim monies";
			$reclaim_jet[] = "Award credit reclaim monies";
        }
		// Get's the full list of points awarded and their expiration dates
		// Note, we must get the full list awards to the user so we don't need to join back to the
		// roles or programs this way. However, we do need to join back to the AWARDING program via the journal
		// event so that we can tell where this award originated from

        $query = DB::table('users');

        $query->addSelect(
            DB::raw("DISTINCT postings.id")
        );

        $query->addSelect([
            'users.account_holder_id AS user_account_holder_id',
            'users.email AS user_email',
            'postings.posting_amount AS amount',
            'postings.created_at AS awarded',
            'journal_events.id as journal_event_id',
            'journal_events.prime_account_holder_id as manager_id',
            'u2.email as manager_email',
            'event_xml_data.name as event_name',
            'event_xml_data.id as event_xml_data_id',
            'event_xml_data.event_template_id',
            'programs.account_holder_id as program_id',
            'programs.name as program_name',
        ]);

        $query->addSelect(
            DB::raw("
                CAST(
                    IF (
                        statuses.status = '" . User::STATUS_PENDING_DEACTIVATION . "' AND users.deactivated < '{$end_date_sql}',
                        users.deactivated,
                        '{$end_date_sql}'
                    ) AS DATETIME
                ) AS expiration
            ")
        );

        // $query->addSelect(['events.award_credit']); //This field is missing right now! TODO

        $query->leftJoin('statuses', 'statuses.id', '=', 'users.user_status_id');
        $query->leftJoin('accounts', 'accounts.account_holder_id', '=', 'users.account_holder_id');
        $query->leftJoin('account_types', 'account_types.id', '=', 'accounts.account_type_id');
        $query->leftJoin('postings', 'postings.account_id', '=', 'accounts.id');
        $query->leftJoin('journal_events', 'journal_events.id', '=', 'postings.journal_event_id');
        $query->leftJoin('event_xml_data', 'event_xml_data.id', '=', 'journal_events.event_xml_data_id');
        $query->leftJoin('events', 'events.id', '=', 'event_xml_data.event_template_id');
        $query->leftJoin('postings AS program_posting', 'program_posting.journal_event_id', '=', 'journal_events.id');
        $query->leftJoin('accounts AS program_accounts', 'program_accounts.id', '=', 'program_posting.account_id');
        $query->join('programs', 'programs.account_holder_id', '=', 'program_accounts.account_holder_id');
        $query->leftJoin('users as u2', 'u2.account_holder_id', '=', 'journal_events.prime_account_holder_id');

        $query->where('users.account_holder_id', '=', $user->account_holder_id);
        $query->where('account_types.name', '=', $account_name);
        $query->where('postings.is_credit', '=', 1);

        $query->orderBy('postings.created_at', 'ASC');

        try {
            $result = $query->get();
            
            if( $result->isNotEmpty() )
            {
                // Get the points redeemed and expired
                $points_redeemed = $this->accountService->readRedeemedTotalForParticipant ( $program, $user );
                $points_expired = $this->accountService->readExpiredTotalForParticipant ( $program, $user );
                
                // // Get the total amount reclaimed, we can use this to verify that the "smart" whittle of reclaims was successful
                $points_reclaimed = $this->accountService->readReclaimedTotalForParticipant ( $program, $user );

                // pr($points_reclaimed);
                // pr($points_expired);
                // // Get the full list of reclaims, we need to do a "smart" whittle on these so that we...
                $points_reclaimed_list = $this->accountService->readListParticipantPostingsByAccountAndJournalEvents ( $user->account_holder_id, $account_name, $reclaim_jet, 0 );

                // ..."Smart" Whittle away the reclaims first making sure to match them up with the program id they were reclaimed to.
                if ( $points_reclaimed_list->isNotEmpty() ) {
                    foreach ( $points_reclaimed_list as $reclaim_posting ) {
                        if ($points_reclaimed <= 0) {
                            break;
                        }
                        foreach ( $result as &$point_award3 ) {
                            if ($points_reclaimed <= 0) {
                                break;
                            }
                            if ($reclaim_posting->posting_amount_total <= 0) {
                                break;
                            }
                            if ($point_award3->amount <= 0) {
                                continue;
                            }
                            // If the program id does not match where the reclaim happened, skip it
                            if ($point_award3->program_id != $reclaim_posting->program_id) {
                                continue;
                            }
                            if ($point_award3->journal_event_id != $reclaim_posting->parent_journal_event_id) {
                                continue;
                            }
                            if ($reclaim_posting->posting_amount_total <= $point_award3->amount) {
                                $point_award3->amount -= $reclaim_posting->posting_amount_total;
                                $points_reclaimed -= $reclaim_posting->posting_amount_total;
                                $reclaim_posting->posting_amount_total = 0;
                            } else {
                                $reclaim_posting->posting_amount_total -= $point_award3->amount;
                                $points_reclaimed -= $point_award3->amount;
                                $point_award3->amount = 0;
                            }
                        }
                    }
                }
                // Finish the reclaim whittle using the regular method in case of errors
                foreach ( $result as &$point_award4 ) {
                    if ($points_reclaimed <= 0) {
                        break;
                    }
                    if ($point_award4->amount <= 0) {
                        continue;
                    }
                    if ($points_reclaimed <= $point_award4->amount) {
                        $point_award4->amount -= $points_reclaimed;
                        $point_award4->amount = round($point_award4->amount, 4, PHP_ROUND_HALF_DOWN);
                        $points_reclaimed = 0;
                    } else {
                        $points_reclaimed -= $point_award4->amount;
                        $point_award4->amount = 0;
                    }
                }
                // Whittle away the points awarded by subtracting out the points redeemed and expired and removing entries that fall to 0
                // take away points that have been redeemed since we care about
                foreach ( $result as &$point_award ) {
                    if ($points_redeemed <= 0) {
                        break;
                    }
                    if ($point_award->amount <= 0) {
                        continue;
                    }
                    if ($points_redeemed <= $point_award->amount) {
                        $point_award->amount -= $points_redeemed;
                        $point_award->amount = round($point_award->amount, 4, PHP_ROUND_HALF_DOWN);
                        $points_redeemed = 0;
                    } else {
                        $points_redeemed -= $point_award->amount;
                        $point_award->amount = 0;
                    }
                }
                // take away points that have expired
                foreach ( $result as &$point_award2 ) {
                    if ($points_expired <= 0) {
                        break;
                    }
                    if ($point_award2->amount <= 0) {
                        continue;
                    }
                    if ($points_expired <= $point_award2->amount) {
                        $point_award2->amount -= $points_expired;
                        $point_award2->amount = round($point_award2->amount, 4, PHP_ROUND_HALF_DOWN);
                        $points_expired = 0;
                    } else {
                        $points_expired -= $point_award2->amount;
                        $point_award2->amount = 0;
                    }
                }
                // Remove any point awards that are now at 0
                for($i = count ( $result ) - 1; $i >= 0; -- $i) {
                    if ($result [$i]->amount <= 0) {
                        unset ( $result [$i] );
                    }
                }
                // return array_values ( array_reverse( $result) );
                // Returning "points" info as well so it can be used further
                return [
                    'expiration' => $result->toArray(),
                    'points_redeemed' => $points_redeemed,
                    'points_expired' => $points_expired,
                    'points_reclaimed' => $points_reclaimed,
                ];
            }
        } catch (Exception $e) {
            throw new Exception(sprintf('DB query failed for "%s" in line %d', $e->getMessage(), $e->getLine()), 500);
        }
    }
}
