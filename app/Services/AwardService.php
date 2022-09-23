<?php

namespace App\Services;

use App\Models\Account;
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

    public function __construct(
        ProgramService $programService,
        EventXmlDataService $eventXmlDataService,
        JournalEventService $journalEventService,
        AccountService $accountService
    )
    {
        $this->programService = $programService;
        $this->eventXmlDataService = $eventXmlDataService;
        $this->journalEventService = $journalEventService;
        $this->accountService = $accountService;
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

        if($eventType->isEventTypePeer2PeerAllocation()){
            $newAward = $this->allocatePeer2Peer($program, $currentUser, $data);
        } else {
            $newAward = Award::create(
                (object) ($data +
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
            foreach( $users as $user)    {
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
}
