<?php

namespace App\Services;

use App\Models\SocialWallPostType;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Exception;

use App\Models\BudgetCascadingApproval;
use App\Models\JournalEventType;
use App\Models\Organization;
use App\Models\EventXmlData;
use App\Models\JournalEvent;
use App\Models\AccountType;
use App\Models\FinanceType;
use App\Models\MediumType;
use App\Models\EventType;
use App\Models\Currency;
use App\Models\Program;
use App\Models\Account;
use App\Models\Event;
use App\Models\Owner;
use App\Models\User;
use Carbon\Carbon;
use App\Notifications\AwardNotification;

class AwardService
{
    /**
     * @param Program $program
     * @param Organization $organization
     * @param User $currentUser
     * @param array $data
     * @return array
     * @throws Exception
     */
    protected bool $isCron = false;
    protected function setIsCron($bool)
    {
        $this->isCron = $bool;
    }
    protected function isCron()
    {
        return $this->isCron;
    }

    public function awardMany(Program $program, Organization $organization, User $awarder, array $data)
    {

        $userIds = $data['user_id'] ?? [];

        if (sizeof($userIds) <= 0) {
            throw new InvalidArgumentException('Invalid or no "recipients" passed', 400);
        }

        if (in_array($awarder->id, $userIds)) {
            throw new InvalidArgumentException('Invalid "user" passed, you can\'t award your own account', 400);
        }

        /** @var Event $event */
        $event = Event::findOrFail($data['event_id']);
        /** @var EventType $eventType */
        $eventType = $event->eventType()->firstOrFail();

        if ($eventType->isEventTypePeer2PeerAllocation()) {
            $newAward = $this->allocatePeer2Peer($program, $awarder, $data);
            return $newAward;
        }

        $award = (object)($data +
            [
                'organization_id' => $organization->id,
                'program_id' => $program->id
            ]);

        $isBadge = $eventType->isEventTypeBadge();
        $isPeer2peer = $eventType->isEventTypePeer2Peer();
        $isPeer2peerBadge = $eventType->isEventTypePeer2PeerBadge();

        $overrideCashValue = $award->override_cash_value ?? 0;
        $eventAmountOverride = $overrideCashValue > 0;
        $awardAmount = $eventAmountOverride ? $overrideCashValue : $event->max_awardable_amount;

        if ($isPeer2peerBadge) {
            $isBadge = true;
            $isPeer2peer = true;
            $awardAmount = 0;
        }
        if ($isBadge) {
            $awardAmount = 0;
        }
        if ($isPeer2peer) {
            if (!$this->canPeerPayForAwards($program, $awarder, $awardAmount, $userIds)) {
                throw new Exception('Your program\'s account balance is too low to award.');
            }
        }

        if ($program->isShellProgram()) {
            throw new \RuntimeException('Invalid "receiver program", you cannot create an award in a shell program', 400);
        }

        $programService = resolve(\App\Services\ProgramService::class);

        if (!$programService->canProgramPayForAwards($program, $event, $userIds, $awardAmount)) {
            throw new Exception('Your program\'s account balance is too low to award.');
        }

        $result = null;

        try {

            $users = User::whereIn('id', $award->user_id)->get();

            foreach ($users as $user) {
                $result[$user->id] = $this->awardUser($event, $user, $awarder, $award);
                $this->SaveBudgetCascadingApprovalDetail($event, $user, $awarder, $award);
            }

            // print_r( $journalEventType );

            // TODO
            // // Read the award levels assigned to the event
            // $assigned_award_levels = $this->event_templates_model->read_list_of_event_award_level_by_event ( $receiver_program_id, $event_template_id, 0, 99999 );
            // if (! is_array ( $assigned_award_levels ) || count ( $assigned_award_levels ) < 1) {
            // 	throw new RuntimeException ( "This event template cannot be used because it does not have any award levels assigned" );
            // }
        } catch (Exception $e) {
            $result['error'] = "Error while processing awarding. Error:{$e->getMessage()} in line {$e->getLine()}";
            DB::rollBack();
        }

        return $result;
    }

    public function SaveBudgetCascadingApprovalDetail($event, $user, $awarder, object $data = null)
    {
        $program = $event->program;
        $event_award = $event->event_award_level;
        $transaction_id = generate_unique_id();
        $awardData = [];
        foreach ($event_award as $eventAwardLevel) {
            if ($program->use_cascading_approvals && $event->include_in_budget) {
                BudgetCascadingApproval::create([
                    'parent_id' => $program->parent_id,
                    'awarder_id' => $awarder->id,
                    'user_id' => $user->id,
                    'requestor_id' => $awarder->id,
                    'manager_id' => 0,
                    'event_id' => $event->id,
                    'award_id' =>  $eventAwardLevel->award_level_id,
                    'amount' => $eventAwardLevel->amount,
                    'approved' => 0,
                    'award_data' =>  json_encode($awardData),
                    'transaction_id' => $transaction_id,
                    'program_approval_id' => 0,
                    'program_id' => $data->program_id,
                    'include_in_budget' => $event->include_in_budget,
                    'budgets_cascading_id' =>$data->budget_cascading_id,
                    'rejection_note' => "",
                    'scheduled_date' => Carbon::now(),
                    'action_by' => $awarder->id,
                ]);
            }
        }
    }

    public function awardUser($event, $awardee, $awarder, object $data = null, $dontSendEmail = null)
    {
        //        $statement = "LOCK TABLES programs READ, postings WRITE, medium_info WRITE, journal_events WRITE;";
        //        DB::statement($statement);
        DB::beginTransaction();

        $program = $event->program;
        $factor_valuation = $program->factor_valuation;
        $escrowCreditAccountTypeName = $escrowAccountTypeName = "";

        $organization_id = $data->organization_id ?? $program->organization_id;
        $eventType = $event->eventType()->firstOrFail();
        
        $isCustom = $eventType->isEventTypeCustom();
        $isBadge = $eventType->isEventTypeBadge();
        $isPeer2peer = $eventType->isEventTypePeer2Peer();
        $isAutoAward = $eventType->isEventTypeAutoAward();
        $isMilestoneAward = $eventType->isEventTypeMilestoneAward();
        $isMilestoneBadge = $eventType->isEventTypeMilestoneBadge();
        $isPeer2peerBadge = $eventType->isEventTypePeer2PeerBadge();
        $isBirthdayAward = $eventType->isEventTypeBirthdayAward();
        $isBirthdayBadge = $eventType->isEventTypeBirthdayBadge();
        $isPromotional = $event->is_promotional;
        $overrideCashValue = $data->override_cash_value ?? 0;
        $eventAmountOverride = $overrideCashValue > 0;
        $awardAmount = $eventAmountOverride ? $overrideCashValue : $event->max_awardable_amount;
        $awardPoints = $awardAmount * $factor_valuation;

        // check for peer2peer and badge type
        if ($isPeer2peerBadge) {
            $isPeer2peer = true;
        }
        if ($isPeer2peerBadge || $isMilestoneBadge ||  $isBirthdayBadge) {
            $isBadge = true;
        }

        //Set notification type
        $notificationType = 'Award';

        if( $isCustom )
        {
            $notificationType = 'CustomAward';
        }
        if( $isBadge )
        {
            $notificationType = 'BadgeAward';
        }
        if ($isMilestoneAward) {
            $notificationType = 'MilestoneAward';
        }
        if ($isMilestoneBadge) {
            $notificationType = 'MilestoneBadge';
        }
        if ($isBirthdayAward) {
            $notificationType = 'BirthdayAward';
        }
        if ($isBirthdayBadge) {
            $notificationType = 'BirthdayBadge';
        }
        if ($isPeer2peer) {
            $notificationType = 'PeerAward';
        }

        // Set amount 0 for badge awards
        if ($isBadge) {
            $awardAmount = 0;
        }
        if ($isPeer2peer) {
            if (!$this->canPeerPayForAwards($program, $awarder, $awardAmount, [$awardee->id])) {
                throw new Exception('Your program\'s account balance is too low to award.');
            }
            $escrowAccountTypeName = AccountType::ACCOUNT_TYPE_PEER2PEER_POINTS;
        }

        $transactionFee = 0;

        if ($isAutoAward || !$isPromotional) {
            if ($awardAmount > 0) {
                $transactionFee = (new \App\Services\ProgramsTransactionFeeService)->calculateTransactionFee($program, $awardAmount);
                $programService = resolve(\App\Services\ProgramService::class);
                if ($transactionFee > 0 && !$programService->canProgramPayForAwards($program, $event, [$awardee->id], $transactionFee)) {
                    throw new \RuntimeException("The program's balance is too low.", 400);
                }
            }
        }

        $userId = $awardee->id;
        $isInvoice4Awards = $program->programIsInvoiceForAwards();

        $awardUniqId = generate_unique_id();
        $token = uniqid();
        $event_id = $event->id;
        $eventTypeId = $event->event_type_id;
        $eventName = $event->name;
        $awarderAccountHolderId = $awarder->account_holder_id;

        $notificationBody = $data->message ?? ''; //TODO
        $restrictions = $data->restrictions ?? '';
        $notes = $data->notes ?? '';

        $referrer = $data->referrer ?? null;
        $leaseNumber = $data->lease_number ?? null;

        $overrideCashValue = $data->override_cash_value ?? 0;
        $eventAmountOverride = $overrideCashValue > 0;
        $awardAmount = $eventAmountOverride ? $overrideCashValue : $event->max_awardable_amount;
        $awardPoints = $awardAmount * $factor_valuation;

        // $notificationBody = $data->message; //TODO

        $userAccountHolderId = $awardee->account_holder_id;
        // continue;
        if ($isInvoice4Awards) {
            $journalEventType = JournalEventType::JOURNAL_EVENT_TYPES_AWARD_POINTS_TO_RECIPIENT;
        } else {
            $journalEventType = JournalEventType::JOURNAL_EVENT_TYPES_AWARD_MONIES_TO_RECIPIENT;
        }

        if ($event->only_internal_redeemable && $program->show_internal_store) {
            $journalEventType = JournalEventType::JOURNAL_EVENT_TYPES_REDEEMABLE_ON_INTERNAL_STORE;
            $escrowCreditAccountTypeName = AccountType::ACCOUNT_TYPE_INTERNAL_STORE_POINTS;
        } else if ($isPromotional) {
            $journalEventType = JournalEventType::JOURNAL_EVENT_TYPES_PROMOTIONAL_AWARD;
            $escrowCreditAccountTypeName = AccountType::ACCOUNT_TYPE_PROMOTIONAL_POINTS;
        }

        $journalEventTypeId = JournalEventType::getIdByType($journalEventType);

        $liability = FinanceType::getIdByName('Liability');
        $asset = FinanceType::getIdByName('Asset', true);
        $points = MediumType::getIdByName('Points', true);
        $monies = MediumType::getIdByName('Monies', true);
        $currency_id = Currency::getIdByType(config('global.default_currency'), true);
        $socialWallPostType = SocialWallPostType::getEventType();

        $eventXmlDataID = EventXmlData::insertGetId([
            'awarder_account_holder_id' => $awarderAccountHolderId,
            'name' => $eventName,
            'award_level_name' => 'default', //TODO
            'amount_override' => $eventAmountOverride,
            'notification_body' => $notificationBody,
            'notes' => $notes,
            'restrictions' => $restrictions,
            'referrer' => $referrer,
            'lease_number' => $leaseNumber,
            'token' => $token,
            'email_template_id' => $data->email_template_id ?? 1, // TODO: email templates
            'event_type_id' => $eventTypeId,
            'icon' => 'Award', //TODO
            'event_template_id' => $event_id, //Event > id
            'award_transaction_id' => $awardUniqId,
            'created_at' => now()
        ]);

        $result[$userId]['event_xml_data_id'] = $eventXmlDataID;
        $result[$userId]['userAccountHolderId'] = $userAccountHolderId;

        $journalEventID = JournalEvent::insertGetId([
            'journal_event_type_id' => $journalEventTypeId,
            'event_xml_data_id' => $eventXmlDataID,
            'notes' => $notes,
            'restrictions' => $restrictions,
            'prime_account_holder_id' => $awarderAccountHolderId,
            'created_at' => now()
        ]);

        if ($escrowAccountTypeName != "") {
            $result[$userId]['escrow_postings'] = Account::postings(
                $awarderAccountHolderId,
                $escrowAccountTypeName,
                $liability,
                $isInvoice4Awards ? $points : $monies,
                $program->account_holder_id,
                $escrowCreditAccountTypeName,
                $liability,
                $isInvoice4Awards ? $points : $monies,
                $journalEventID,
                $awardAmount,
                1, //qty
                null, // medium_info
                null, // medium_info_id
                $currency_id
            );
        }

        // pr('Run > awarder_postings');
        if ($isInvoice4Awards) {
            // First posting i.e. if( $escrowAccountTypeName != "")... is done outside of this condition
            // "Monies Due to Owner/Points Available(conditional))" postings
            $creditAccountTypeName = $escrowCreditAccountTypeName ? $escrowCreditAccountTypeName : AccountType::ACCOUNT_TYPE_POINTS_AVAILABLE;
            $result[$userId]['awarder_postings'] = Account::postings(
                $program->account_holder_id,
                AccountType::ACCOUNT_TYPE_MONIES_DUE_TO_OWNER,
                $asset,
                $monies,
                $program->account_holder_id,
                $creditAccountTypeName,
                $liability,
                $points,
                $journalEventID,
                $awardAmount,
                1, //qty
                null, // medium_info
                null, // medium_info_id
                $currency_id
            );
            // "Points Available/Points Awarded(conditional)" postings
            $creditAccountTypeName = $escrowCreditAccountTypeName ? $escrowCreditAccountTypeName : AccountType::ACCOUNT_TYPE_POINTS_AWARDED;
            $result[$userId]['awarder_postings'] = Account::postings(
                $program->account_holder_id,
                AccountType::ACCOUNT_TYPE_POINTS_AVAILABLE,
                $liability,
                $points,
                $awardee->account_holder_id,
                $creditAccountTypeName,
                $liability,
                $points,
                $journalEventID,
                $awardAmount,
                1, //qty
                null, // medium_info
                null, // medium_info_id
                $currency_id
            );

            // "Monies Due to Owner/Monies Fees" (transaction fee) postings
            $creditAccountTypeName = $escrowCreditAccountTypeName ? $escrowCreditAccountTypeName : AccountType::ACCOUNT_TYPE_MONIES_FEES;
            $result[$userId]['awarder_postings'] = Account::postings(
                $program->account_holder_id,
                AccountType::ACCOUNT_TYPE_MONIES_DUE_TO_OWNER,
                $asset,
                $monies,
                $program->account_holder_id,
                $creditAccountTypeName,
                $liability,
                $monies,
                $journalEventID,
                $transactionFee,
                1, //qty
                null, // medium_info
                null, // medium_info_id
                $currency_id
            );
        } else {
            // First posting i.e. if( $escrowAccountTypeName != "")... is done outside of this condition
            // 1st "Monies Available/Monies Awarded" postings
            $creditAccountTypeName = $escrowCreditAccountTypeName ? $escrowCreditAccountTypeName : AccountType::ACCOUNT_TYPE_MONIES_AWARDED;
            // dump($creditAccountTypeName);
            $result[$userId]['awarder_postings'] = Account::postings(
                $program->account_holder_id,
                AccountType::ACCOUNT_TYPE_MONIES_AVAILABLE,
                $asset,
                $monies,
                $awardee->account_holder_id,
                $creditAccountTypeName,
                $liability,
                $monies,
                $journalEventID,
                $awardAmount,
                1, //qty
                null, // medium_info
                null, // medium_info_id
                $currency_id
            );
            // "Monies Available/Monies Fees" postings
            $result[$userId]['awarder_postings'] = Account::postings(
                $program->account_holder_id,
                AccountType::ACCOUNT_TYPE_MONIES_AVAILABLE,
                $asset,
                $monies,
                $program->account_holder_id,
                AccountType::ACCOUNT_TYPE_MONIES_FEES,
                $liability,
                $monies,
                $journalEventID,
                $transactionFee,
                1, //qty
                null, // medium_info
                null, // medium_info_id
                $currency_id
            );
        }
        // print_r( $userId );

        // If the program uses leaderboards get all the leaderboards that are tied to this event
        if ($program->uses_leaderboards) {
            $leaderboardService = new LeaderboardService();
            $leaderboardService->createLeaderboardJournalEvent($event_id, $journalEventID);
        }

        $notification = [
            'notificationType' => $notificationType,
            'awardee_first_name' => $awardee->first_name,
            'awardPoints' => (int) $awardPoints,
            'awardNotificationBody' => $notificationBody,
            'program' => $program,
            'eventName' => $eventName,
        ];

        if ($notificationType == 'PeerAward') {
            $notification['awarder_first_name'] = $awarder->first_name;
            $notification['awarder_last_name'] = $awarder->last_name;
            $notification['availableAwardPoints'] = $awardee->readAvailableBalance($program) * $factor_valuation;
        }

        if( $notificationType == 'CustomAward')
        {
            $notification['restrictions'] = $restrictions;
        }

        // If the event template used has post to social wall turned on. Create a new social wall post
        if ($program->uses_social_wall && $event->post_to_social_wall) {
            $socialWallPostData = [
                'social_wall_post_type_id' => $socialWallPostType->id,
                'social_wall_post_id' => null,
                'event_xml_data_id' => $eventXmlDataID,
                'program_id' => $program->id,
                'organization_id' => $organization_id,
                'sender_user_account_holder_id' => $awarderAccountHolderId,
                'receiver_user_account_holder_id' => $userAccountHolderId,
            ];
            $socialWallPostService = resolve(\App\Services\SocialWallPostService::class);
            $socialWallPostService->create($socialWallPostData, $program);
        }

        if ($awardee->status()->first()->status == User::STATUS_NEW) {
            $token = \Illuminate\Support\Facades\Password::broker()->createToken($awardee);
            event(new \App\Events\UserInvited($awardee, $program, $token));
        }

        if (isset($dontSendEmail) && $dontSendEmail) {
            // no notification
        } else {
            $awardee->notify(new AwardNotification((object)$notification));
        }

        (new \App\Services\PushNotificationService)->notifyUser($awardee, [
            'title' => "You have a new reward!",
            'body' => $notificationBody,
            'data' => [ //to be consumed by the mobile app
                'points_awarded' => [
                    'points' => (int) $awardPoints,
                    'amount' => $awardAmount
                ]
            ]
        ]);

        // DB::rollBack();
        DB::commit();
        // DB::statement("UNLOCK TABLES;");

        return $result;
    }
    public function awardPeer2Peer(array $data, Event $event, Program $program, User $awarder)
    {
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
    public function allocatePeer2Peer(Program $program, User $currentUser, array $data)
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

        $programService = resolve(\App\Services\ProgramService::class);

        // return $programService->readAvailableBalance($program);

        if (!$programService->canProgramPayForAwards($program, $event, $userIds, $amount)) {
            throw new Exception('Your program\'s account balance is too low to allocate peer.');
        }

        if ($program->isShellProgram()) {
            throw new InvalidArgumentException('Invalid program passed, you cannot create an award in a shell program');
        }

        if (!$program->uses_peer2peer) {
            throw new InvalidArgumentException('This program does not allow peer 2 peer allocation');
        }

        foreach ($userIds as $userId) {
            /** @var User $user */
            $user = User::findOrFail($userId);

            if (!$user->canBeAwarded($program)) {
                throw new InvalidArgumentException("User cannot be rewarded. User Id: {$userId}");
            }
        }

        if (in_array($currentUser->id, $userIds)) {
            throw new InvalidArgumentException('You can\'t award your own account');
        }

        if (!$eventType->isEventTypePeer2PeerAllocation()) {
            throw new InvalidArgumentException("Event type must be peer2peer allocation. Current type: {$event->eventType()->first()->type}");
        }

        $result = [];

        try {
            $users = User::whereIn('id', $userIds)->select(['id', 'account_holder_id', 'first_name'])->get();
            $event->program = $program;
            foreach ($users as $user) {
                $this->awardPeer($event, $user, $currentUser, $data);
            }
        } catch (Exception $e) {
            DB::rollBack();
            throw $e;
        }
        return $result;
    }

    public function awardPeer(Event $event, User $awardee, User $awarder, array $data = null)
    {
        DB::beginTransaction();

        try {
            $userId = $awardee->id;
            $userAccountHolderId = $awardee->account_holder_id;
            $program = $event->program;

            $overrideCashValue = $data['override_cash_value'] ?? 0;
            $eventAmountOverride = $overrideCashValue > 0;
            $amount = $eventAmountOverride ? $overrideCashValue : $event->max_awardable_amount;
            $amount = (float)$amount;
            $awardPoints = $amount * $program->factor_valuation;

            $notificationBody = $data['message'] ?? '';

            $data = [
                'awarder_account_holder_id' => $awarder->account_holder_id,
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
            $eventXmlDataService = resolve(\App\Services\EventXmlDataService::class);
            $eventXmlDataId = $eventXmlDataService->create($data);

            $result[$userId]['event_xml_data_id'] = $eventXmlDataId;
            $result[$userId]['userAccountHolderId'] = $userAccountHolderId;

            $journalEventTypeId = JournalEventType::getTypeAllocatePeerPoints();
            $data = [
                'journal_event_type_id' => $journalEventTypeId,
                'event_xml_data_id' => $eventXmlDataId,
                'notes' => $data['notes'] ?? '',
                'prime_account_holder_id' => $awarder->account_holder_id,
            ];
            $journalEventService = resolve(\App\Services\JournalEventService::class);
            $journalEventId = $journalEventService->create($data);

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
            $accountService = resolve(\App\Services\AccountService::class);
            $result[$userId]['recipient_postings'] = $accountService->posting($data);

            $awardee->notify(new AwardNotification((object)[
                'notificationType' => 'PeerAllocation',
                'awardee_first_name' => $awardee->first_name,
                'awardPoints' => (int) $awardPoints,
                'awardNotificationBody' => $notificationBody,
                'program' => $program
            ]));
            (new \App\Services\PushNotificationService)->notifyUser($awardee, [
                'title' => "You have a new peer reward!",
                'body' => $notificationBody,
                'data' => [ //to be consumed by the mobile app
                    'points_awarded' => [
                        'points' => (int) $awardPoints,
                        'amount' => $amount
                    ]
                ]
            ]);
            DB::commit();
        } catch (\RuntimeException $e) {
            cronlog(sprintf('ERROR: could not award user:%d for error:%s', $awardee->id, $e->getMessage()));
            DB::rollBack();
        }
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
        $userService = resolve(\App\Services\UserService::class);
        $available = $userService->readAvailablePeerBalance($user, $program);
        $awardTotal = count($users) * $amount;
        if ($available < $awardTotal) {
            return false;
        }
        return true;
    }

    public function readEventHistoryByParticipant(
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
        $query->leftJoin('events', 'events.id', '=', 'event_xml_data.event_template_id');
        $query->leftJoin('event_icons', 'event_icons.id', '=', 'events.event_icon_id');

        $query->addSelect(
            DB::raw("
            if(`event_xml_data`.name is null, `journal_event_types`.type, `event_xml_data`.name) AS name,
				`accounts`.id AS account_id,
				`journal_events`.id AS journal_event_id,
				`journal_events`.event_xml_data_id,
				`event_icons`.path as icon,
				if(is_credit = 1, `postings`.posting_amount, -`postings`.posting_amount) AS amount ,
				`event_xml_data`.award_level_name ,
				if(`event_xml_data`.notes is null, `journal_events`.notes, `event_xml_data`.notes) AS notes,
                if(`event_xml_data`.restrictions is null, `journal_events`.restrictions, `event_xml_data`.restrictions) AS restrictions,
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
        $query->whereIn('account_types.name', [AccountType::ACCOUNT_TYPE_POINTS_AWARDED, AccountType::ACCOUNT_TYPE_MONIES_AWARDED]);
        if (isset($extraArgs['onlyAwards']) && $extraArgs['onlyAwards'] == 1) {
            $query->whereNotNull('event_xml_data.id');
        }
        $query->orderBy($order_column, $order_direction);

        try {
            return [
                'total' => $query->count(),
                'data' => $query->limit($limit)->offset($offset)->get()
            ];
        } catch (Exception $e) {
            throw new Exception('DB query failed.', 500);
        }
    }

    public function readListExpireFuture(Program $program, User $user)
    {
        $end_date_sql = $program->getPointsExpirationDateSql();

        // build and run the query and store it into the $query variable for
        // later use and validation of the $query object

        $reclaim_jet = [];
        if ($program->programIsInvoiceForAwards()) {
            $account_name = AccountType::ACCOUNT_TYPE_POINTS_AWARDED;
            $reclaim_jet[] = "Reclaim points";
            $reclaim_jet[] = "Award credit reclaim points";
        } else {
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
            DB::raw("'Disabled on Program Level' as award_credit"),
            DB::raw("expiration_rules.description as expiration_description"),
            DB::raw("postings.id as 'key'")
        ]);

        $query->addSelect(
            DB::raw("
                CAST(
                    IF (
                        statuses.status = '" . User::STATUS_PENDING_DEACTIVATION . "' AND users.deactivated < {$end_date_sql},
                        users.deactivated,
                        {$end_date_sql}
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
        $query->leftJoin('expiration_rules', 'expiration_rules.id', '=', 'programs.expiration_rule_id');


        $query->where('users.account_holder_id', '=', $user->account_holder_id);
        $query->where('account_types.name', '=', $account_name);
        $query->where('postings.is_credit', '=', 1);

        $query->orderBy('postings.created_at', 'ASC');

        try {
            $result = $query->get();

            if ($result->isNotEmpty()) {
                $accountService = resolve(\App\Services\AccountService::class);
                // Get the points redeemed and expired
                $points_redeemed = $accountService->readRedeemedTotalForParticipant($program, $user);
                $points_expired = $accountService->readExpiredTotalForParticipant($program, $user);

                // // Get the total amount reclaimed, we can use this to verify that the "smart" whittle of reclaims was successful
                $points_reclaimed = $accountService->readReclaimedTotalForParticipant($program, $user);

                // pr($points_reclaimed);
                // pr($points_expired);
                // // Get the full list of reclaims, we need to do a "smart" whittle on these so that we...
                $points_reclaimed_list = $accountService->readListParticipantPostingsByAccountAndJournalEvents($user->account_holder_id, $account_name, $reclaim_jet, 0);

                // ..."Smart" Whittle away the reclaims first making sure to match them up with the program id they were reclaimed to.
                if ($points_reclaimed_list->isNotEmpty()) {
                    foreach ($points_reclaimed_list as $reclaim_posting) {
                        if ($points_reclaimed <= 0) {
                            break;
                        }
                        foreach ($result as &$point_award3) {
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
                foreach ($result as &$point_award4) {
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
                foreach ($result as &$point_award) {
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
                foreach ($result as &$point_award2) {
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
                for ($i = count($result) - 1; $i >= 0; --$i) {
                    if ($result[$i]->amount <= 0) {
                        unset($result[$i]);
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

    /** readListReclaimablePeerPointsByProgramAndUser
     * v2 Alias: read_list_reclaimable_peer_points_by_program_and_user
     * @param Program $program
     * @param User $user
     * @return Collection
     */
    public function readListReclaimablePeerPointsByProgramAndUser(Program $program, User $user)
    {
        $result = $this->readListUnusedPeerAwards($program, $user);
        return $result;
    }
    /**
     * @method readListUnusedPeerAwards - alias to _read_list_unused_peer_awards
     *
     * @param Program $program
     * @param User $user
     * @return Collection
     * @throws Exception
     */
    private function readListUnusedPeerAwards(Program $program, User $user)
    {
        // build and run the query and store it into the $query variable for
        // later use and validation of the $query object
        $account_name = AccountType::ACCOUNT_TYPE_PEER2PEER_POINTS;
        $reclaim_jet = JournalEventType::JOURNAL_EVENT_TYPES_RECLAIM_PEER_POINTS;

        // if (! $program->programIsInvoiceForAwards ()) {
        //     // throw new Exception('Function unsupported by this program.');
        // 	$account_name = 'Monies Awarded';
        // 	$reclaim_jet = "Reclaim monies";
        // }
        // Get's the full list of points awarded and their expiration dates
        // Note, we must get the full list awards to the user so we don't need to join back to the
        // roles or programs this way. However, we do need to join back to the AWARDING program via the journal
        // event so that we can tell where this award originated from
        $query = DB::table('users');

        $query->addSelect(
            DB::raw("DISTINCT postings.id")
        );
        $query->addSelect([
            'users.id AS user_id',
            'postings.posting_amount AS amount',
            'postings.created_at AS awarded',
            'journal_events.id as journal_event_id',
            'p.id as program_id',
            'event_xml_data.name as event_name',
            'account_types.name as account_type_name',
        ]);
        $query->leftJoin('statuses', 'statuses.id', '=', 'users.user_status_id');
        $query->leftJoin('accounts', 'accounts.account_holder_id', '=', 'users.account_holder_id');
        $query->leftJoin('account_types', 'account_types.id', '=', 'accounts.account_type_id');
        $query->leftJoin('postings', 'postings.account_id', '=', 'accounts.id');
        $query->leftJoin('journal_events', 'journal_events.id', '=', 'postings.journal_event_id');
        $query->leftJoin('event_xml_data', 'event_xml_data.id', '=', 'journal_events.event_xml_data_id');
        $query->leftJoin('postings As program_posting', 'program_posting.journal_event_id', '=', 'journal_events.id');
        $query->join('accounts AS program_accounts', 'program_accounts.id', '=', 'program_posting.account_id');
        $query->join('programs AS p', 'p.account_holder_id', '=', 'program_accounts.account_holder_id');

        $query->where('users.id', '=', $user->id);
        $query->where('account_types.name', 'LIKE', $account_name);
        $query->where('postings.is_credit', '=', 1);
        try {
            $result = $query->get();

            if ($result->isNotEmpty()) {
                $accountService = resolve(\App\Services\AccountService::class);
                // Get the points redeemed and expired
                $points_redeemed = $accountService->readRedeemedTotalPeerPointsForParticipant($program, $user);
                // Get the total amount reclaimed, we can use this to verify that the "smart" whittle of reclaims was successfulreadReclaimedTotalPeerPointsForParticipant
                $points_reclaimed = $accountService->readReclaimedTotalPeerPointsForParticipant($program, $user);
                // Get the full list of reclaims, we need to do a "smart" whittle on these so that we
                $points_reclaimed_list = $accountService->readListParticipantPostingsByAccountAndJournalEvents($user->account_holder_id, $account_name, $reclaim_jet, 0);
                // exit;
                // "Smart" Whittle away the reclaims first making sure to match them up with the program id they were reclaimed to.
                if ($points_reclaimed_list->isNotEmpty()) {
                    foreach ($points_reclaimed_list as $reclaim_posting) {
                        if ($points_reclaimed <= 0) {
                            break;
                        }
                        foreach ($result as &$point_award3) {
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
                foreach ($result as &$point_award4) {
                    if ($points_reclaimed <= 0) {
                        break;
                    }
                    if ($point_award4->amount <= 0) {
                        continue;
                    }
                    if ($points_reclaimed <= $point_award4->amount) {
                        $point_award4->amount -= $points_reclaimed;
                        $points_reclaimed = 0;
                    } else {
                        $points_reclaimed -= $point_award4->amount;
                        $point_award4->amount = 0;
                    }
                }
                // Whittle away the points awarded by subtracting out the points redeemed and expired and removing entries that fall to 0
                // take away points that have been redeemed since we care about
                foreach ($result as &$point_award) {
                    if ($points_redeemed <= 0) {
                        break;
                    }
                    if ($point_award->amount <= 0) {
                        continue;
                    }
                    if ($points_redeemed <= $point_award->amount) {
                        $point_award->amount -= $points_redeemed;
                        $points_redeemed = 0;
                    } else {
                        $points_redeemed -= $point_award->amount;
                        $point_award->amount = 0;
                    }
                }
            }
            for ($i = count($result) - 1; $i >= 0; --$i) {
                if ($result[$i]->amount <= 0) {
                    unset($result[$i]);
                }
            }
            return $result->reverse()->values();
        } catch (Exception $e) {
            throw new Exception(sprintf('DB query failed for "%s" in line %d', $e->getMessage(), $e->getLine()), 500);
        }
    }

    /**
     * @method reclaimPeerPoints - v2 (recliam_peer_points)
     *
     * @param Program $program
     * @param User $user
     * @param array $reclaimData
     * */

    public function reclaimPeerPoints(Program $program, User $user, $reclaimData)
    {
        if (sizeof($reclaimData) > 0) {
            $result = [];
            foreach ($reclaimData as $reclaim) {
                $result[$user->id][] = $this->_reclaimPeerPoints($program, $user, $reclaim);
            }
            return $result;
        }
    }

    public function reclaimPoints(Program $program, User $user, array $reclaim)
    {
        $authUser = auth()->user();
        if ($program->programIsInvoiceForAwards(true)) {
            DB::beginTransaction();

            try {
                $journalEventTypeId = JournalEventType::getIdByTypeReclaimPoints();

                $journalEventData = [
                    'parent_journal_event_id' => $reclaim['journal_event_id'],
                    'journal_event_type_id' => $journalEventTypeId,
                    'notes' => strip_tags($reclaim['note'], ALLOWED_HTML_TAGS),
                    'prime_account_holder_id' => $authUser->account_holder_id,
                    'created_at' => now()
                ];

                $journalEventService = resolve(\App\Services\JournalEventService::class);
                $journalEventId = $journalEventService->create($journalEventData);

                $accountTypePeer2PeerPoints = AccountType::getTypeIdPeer2PeerPoints();
                $currencyId = Currency::getDefault();

                $data = [
                    'debit_account_holder_id' => $user->account_holder_id,
                    'debit_account_type_id' => $accountTypePeer2PeerPoints,
                    'debit_finance_type_id' => FinanceType::getIdByTypeLiability(),
                    'debit_medium_type_id' => MediumType::getIdByTypePoints(),
                    'credit_account_holder_id' => $program->account_holder_id,
                    'credit_account_type_id' => $accountTypePeer2PeerPoints,
                    'credit_finance_type_id' => FinanceType::getIdByTypeLiability(),
                    'credit_medium_type_id' => MediumType::getIdByTypePoints(),
                    'journal_event_id' => $journalEventId,
                    'amount' => $reclaim['amount'],
                    'currency_type_id' => $currencyId,
                ];

                $accountService = resolve(\App\Services\AccountService::class);
                $result = $accountService->posting($data);

                DB::commit();
                return $result;
            } catch (\Exception $e) {
                DB::rollback();
                throw $e;
            }
        } else {
            throw new \RuntimeException("Program does not support this function.");
        }
    }

    private function _reclaimPeerPoints(Program $program, User $user, array $reclaim)
    {
        $authUser = auth()->user();
        $reclaimableList = $this->readListReclaimablePeerPointsByProgramAndUser($program, $user);
        $totalReclaimable = 0;
        ['journal_event_id' => $journal_event_id, 'amount' => $amount, 'note' => $notes] = $reclaim;

        $notes = strip_tags($notes, ALLOWED_HTML_TAGS);

        if ($reclaimableList->isNotEmpty()) {
            foreach ($reclaimableList as $reclaimablePosting) {
                if ($reclaimablePosting->program_id != $program->id) {
                    continue;
                }
                $totalReclaimable += $reclaimablePosting->amount;
            }

            if (compare_floats($totalReclaimable, $amount) > 0) {
                throw new InvalidArgumentException("The total reclaimable amount for this user is less than the amount trying to be reclaimed ({$totalReclaimable} < {$amount})");
            }
        }

        if ($program->programIsInvoiceForAwards(true)) {
            $result = null;

            DB::unprepared("LOCK TABLES postings WRITE, medium_info WRITE, journal_events WRITE;");
            DB::beginTransaction();

            $journalEventTypeId = JournalEventType::getIdByTypeReclaimPeerPoints();

            $journalEventData = [
                'parent_journal_event_id' => $journal_event_id,
                'journal_event_type_id' => $journalEventTypeId,
                'notes' => $notes ? $notes : '',
                'prime_account_holder_id' => $authUser->account_holder_id,
                'created_at' => now()
            ];

            $journalEventService = resolve(\App\Services\JournalEventService::class);
            $journalEventId = $journalEventService->create($journalEventData);

            $liability = FinanceType::getIdByTypeLiability();
            $points = MediumType::getIdByTypePoints();

            $currencyId = Currency::getDefault();

            $accountTypePeer2PeerPoints = AccountType::getTypeIdPeer2PeerPoints();

            $data = [
                'debit_account_holder_id' => $user->account_holder_id,
                'debit_account_type_id' => $accountTypePeer2PeerPoints,
                'debit_finance_type_id' => $liability,
                'debit_medium_type_id' => $points,
                'credit_account_holder_id' => $program->account_holder_id,
                'credit_account_type_id' => $accountTypePeer2PeerPoints,
                'credit_finance_type_id' => $liability,
                'credit_medium_type_id' => $points,
                'journal_event_id' => $journalEventId,
                'amount' => $amount,
                'currency_type_id' => $currencyId,
            ];
            $accountService = resolve(\App\Services\AccountService::class);
            $result = $accountService->posting($data);
            DB::commit();
            DB::unprepared("UNLOCK TABLES;");
            return $result;
        } else {
            throw new \RuntimeException("Program does not support this function.");
        }
    }
}
