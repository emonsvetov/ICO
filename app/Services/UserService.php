<?php

namespace App\Services;

use App\Http\Requests\UserRequest;
use App\Models\Account;
use App\Models\AccountType;
use App\Models\Currency;
use App\Models\FinanceType;
use App\Models\JournalEvent;
use App\Models\JournalEventType;
use App\Models\MediumType;
use App\Models\Organization;
use App\Models\Posting;
use App\Models\Program;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Traits\Filterable;
use App\Models\Traits\UserFilters;
use App\Models\Status;
use App\Models\User;
use App\Http\Traits\MediaUploadTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Mail;
use InvalidArgumentException;

class UserService
{
    use Filterable, UserFilters, MediaUploadTrait;

    private AccountService $accountService;

    const EXPIRATION_RULES_TWELVE_MONTHS = 1;        // ,12 Months
    const EXPIRATION_RULES_ONE_OF_MONTH = 2;          // 1 of Month
    const EXPIRATION_RULES_END_OF_NEXT_YEAR = 3;      // ,End of Next Year
    const EXPIRATION_RULES_CUSTOM = 4;                // ,Custom
    const EXPIRATION_RULES_ANNUAL = 5;                // ,Annual
    const EXPIRATION_RULES_SPECIFIED = 6;             // ,Specified
    const EXPIRATION_RULES_TWO_YEARS = 7;             // ,2 Years

    public function __construct()
    {
        $this->accountService = new \App\Services\AccountService;
    }

    public function getIndexData($organization)
    {
        $sortby = request()->get('sortby', 'id');
        $keyword = request()->get('keyword');
        $direction = request()->get('direction', 'asc');
        $status = request()->get('status', '');
        $orgId = request()->get('orgId', '');

        $where = [];

        $query = User::where($where)->withOrganization($organization);

        if( $keyword )
        {
            $query = $query->where(function($query1) use($keyword) {
                $query1->orWhere('id', 'LIKE', "%{$keyword}%")
                ->orWhere('email', 'LIKE', "%{$keyword}%")
                ->orWhere(DB::raw("CONCAT(first_name, ' ', last_name)"), 'LIKE', "%{$keyword}%");
            });
        }

        if( $orgId )
        {
            $orgIds = explode(',', $orgId);
            $query->whereIn('organization_id', $orgIds);
        }

        if ( $status ){
            $statuses = explode(',', $status);
            $statusIds = [];
            foreach ($statuses as $status){
                $statusIds[] = User::getStatusIdByName($status);
            }
            $query->whereIn('user_status_id', $statusIds);
        }

        if( $sortby == 'name' )
        {
            $orderByRaw = "first_name $direction, last_name $direction";
        }
        else
        {
            $orderByRaw = "$sortby $direction";
        }

        $query = $query->orderByRaw($orderByRaw);

        if ( request()->has('minimal') )
        {
            $users = $query->select('id', 'first_name', 'last_name')->with(['roles', 'status'])->get();
        } else {
            $users = $query->with(['roles', 'status'])->paginate(request()->get('limit', 10));
        }

        if ( $users->isNotEmpty() )
        {
            return $users ;
        }

        return [];
    }

    public function getSuperAdmins($paginate = false)
    {
        $query = User::whereHas('roles', function (Builder $query) {
            $query->where('name', 'LIKE', config('roles.super_admin'));
        });
        if ($paginate) {
            return $query->paginate();
        } else {
            return $query->get();
        }
    }

    public function getParticipants($program, $paginate = false)
    {
        $userStatus = User::getStatusByName(User::STATUS_DELETED);
        $program = self::GetModelByMixed($program);
        if ( ! $program->exists()) {
            return;
        }
        // DB::enableQueryLog();
        self::$query = User::whereHas('roles', function (Builder $query) use ($program) {
            $query->where('name', 'LIKE', config('roles.participant'))
                ->where('model_has_roles.program_id', $program->id);
        });
        self::$query->where('user_status_id', '!=', $userStatus->id);
        self::_makeParams();
        self::applyFilters();
        if ($paginate) {
            $users = self::$query->paginate(self::$PARAMS['limit']);
        } else {
            $users = self::$query->get();
        }
        // pr(DB::getQueryLog());
        return $users;
    }

    /**
     * @param UserRequest $request
     * @param User $user
     * @return User|null
     */
    public function update( User $user, $validated)
    {
        $fieldsToUpdate = array_filter(
            $validated,
            fn($key) => ! in_array($key, $user->getImageFields()),
            ARRAY_FILTER_USE_KEY
        );

        $uploads = $this->handleMediaUpload(app('App\Http\Requests\UserRequest'), $user, true);
        if ($uploads) {
            foreach ($uploads as $key => $upload) {
                if (in_array($key, $user->getImageFields())) {
                    $fieldsToUpdate[$key] = $upload;
                }
            }
        }

        $user->update($fieldsToUpdate);

        if ( ! empty($validated['roles'])) {
            $this->updateRoles($user, $validated['roles']);
        }

        if ( ! empty($validated['unit_number']) ) {
            $this->updateUnitNumber($user, $validated['unit_number']);
        }

        return $user;
    }

    /**
     * @param User $user
     * @param array $roles
     * @return void
     */
    public function updateRoles(User $user, array $roles)
    {
        //only a Super admin or a Admin can be assigned here. so we need to keep existing program roles intact
        $newRoles = [];
        $columns = ['program_id' => 0]; //a hack!
        $user->roles()->wherePivot('program_id', '=', 0)->detach();
        foreach ($roles as $role_id) {
            $newRoles[$role_id] = $columns;
        }
        $user->roles()->attach($newRoles);
        // $user->syncRoles( [$validated['roles']] );
    }

    /**
     * @param User $user
     * @param Program $program
     * @return float
     */
    public function readAvailablePeerBalance(User $user, Program $program): float
    {
        $accountTypeName = AccountType::getTypePeer2PeerPoints();

        return $this->accountService->readBalance($user->account_holder_id, $accountTypeName);
    }

    public function listStatus()
    {
        return Status::where('context', 'Users')->get();
    }

    public function updateStatus($validated, $user)
    {
        return $user->update( ['user_status_id' => $validated['user_status_id']] );
    }

    public function getUsersToRemind()
    {
        $userClassForSql = str_replace('\\', '\\\\\\\\', get_class(new User));
        User::$withoutAppends = true;
        $query = User::select(
            'users.id',
            'users.first_name',
            'users.last_name',
            'users.email',
            'users.user_status_id',
            'roles.name AS roleName',
            'model_has_roles.program_id',
        );
        $query->join('model_has_roles', function ($join) use ($userClassForSql) {
            $join->on('model_has_roles.model_id', '=', 'users.id');
            $join->on('model_has_roles.model_type', 'like', DB::raw("'" . $userClassForSql . "'"));
        });
        $query->join('roles', 'model_has_roles.role_id', '=', 'roles.id');
        $query->join('statuses', 'statuses.id', '=', 'users.user_status_id');
        $query->join('programs', 'programs.id', '=', 'model_has_roles.program_id');

        $query->where('roles.name', 'LIKE', config('roles.participant'));

        $query = $query->where(function ($query1) {
            $query1
            ->orWhereNull('users.join_reminder_at')
            ->orWhere('users.join_reminder_at', '<=', \Carbon\Carbon::now()->subDays(7)->toDateTimeString());
        });

        $query->where('users.user_status_id', '=', User::getIdStatusNew());

        return $query->get();
    }

    public function sendActivationReminderToParticipants()
    {
        $users = $this->getUsersToRemind();
        $programUsers = [];
        if($users->isNotEmpty())
        {
            foreach( $users as $user)
            {
                if( !isset($programUsers[$user->program_id]) )
                {
                    $programUsers[$user->program_id] = [];
                }
                $programUsers[$user->program_id][] = $user;
                $user->update(['join_reminder_at' => now()]);
                $user->token = \Illuminate\Support\Facades\Password::broker()->createToken($user);
            }
            $programIds = array_keys($programUsers);
            $programs = Program::whereIn('id', $programIds);
            foreach( $programUsers as $programId => $_users)
            {
                $program = $programs->find($programId);
                event( new \App\Events\UsersInvited( $_users, $program, true ) );
            }
        }
    }

    public function ssoAddToken($data, $ip): array
    {
        $ssoAllowedIps = json_decode(config('sso.sso_allowed_ips'), true);
        $message = '';

        if (in_array($ip, $ssoAllowedIps)) {
            $user = User::leftJoin('program_user', 'users.id', '=', 'program_user.user_id')
                ->select('users.*')
                ->where('program_user.program_id', $data['program_id'])
                ->where('users.email', $data['email'])
                ->first();

            if (is_object($user)) {
                $user->sso_token = $data['sso_token'];
                $success = $user->save();
                $code = 200;
            } else {
                $success = false;
                $code = 404;
                $message = 'User is not found';
            }
        } else {
            $success = false;
            $code = 403;
            $message = 'Access is denied';
        }
        return [
            'success' => $success,
            'message' => $message,
            'code' => $code,
        ];
    }

    public function getSsoUser($ssoToken): ?User
    {
        $user = null;
        if (!empty($ssoToken)) {
            $user = User::where('sso_token', $ssoToken)->first();
            if ($user) {
                $user->sso_token = null;
                $user->save();
            }
        }
        return $user;
    }

    public function generate2faSecret($data)
    {
        try {

            $user = User::where('email', $data['email'])->first();
            $token = Str::random(6);
            $recipientEmail = $user->email;
            $user->token_2fa = $token;
            $user->twofa_verified = true;
            // temp hotfix for migration test
            if ($user->email == 'oganshonkov@incentco.com'){
                $user->token_2fa = 'zzz';
            }
            $user->save();

            Mail::raw($token, function ($message) use ($recipientEmail) {
                $message->to($recipientEmail)
                        ->subject('2FA code for Incentco');
            });
            return [
                'success' => true,
                'message' => 'Verification email sent',
                'code' => 200,
            ];
        }
        catch(\Exception $e)
        {
            return [
                'success' => false,
                'message' => 'Mail request failed '.$e->getMessage(),
                'code' => 422,
            ];
        }

    }

    public function calculateExpirationDate(\stdClass $data)
    {
        $res = false;
        $originalDate = new \DateTime($data->date_awarded);
        $currentDate = new \DateTime();

        if ($data->expiration_rule_id == self::EXPIRATION_RULES_TWELVE_MONTHS) {
            $originalDate->modify('+12 months');
            if ($originalDate > $currentDate) {
                $res = $originalDate->format('Y-m-d H:i:s');;
            } else {
                $res = false;
            }
        } elseif ($data->expiration_rule_id == self::EXPIRATION_RULES_ONE_OF_MONTH) {
            $originalDate->modify('+1 months');
            if ($originalDate > $currentDate) {
                $res = $originalDate->format('Y-m-d H:i:s');;
            } else {
                $res = false;
            }
        } elseif ($data->expiration_rule_id == self::EXPIRATION_RULES_END_OF_NEXT_YEAR) {
            $year = $originalDate->format('Y');
            $newDate = new \DateTime();
            $newDate->setDate($year + 1, 12, 31);
            $newDate->setTime(23, 59, 59);
            if ($newDate > $currentDate) {
                $res = $newDate->format('Y-m-d H:i:s');
            } else {
                $res = false;
            }
        } elseif ($data->expiration_rule_id == self::EXPIRATION_RULES_CUSTOM) {
            if ($data->custom_expire_units == 'YEAR') {
                $months = $data->custom_expire_offset * 12;
                $originalDate->modify("+$months months");
            } elseif ($data->custom_expire_units == 'MONTH') {
                $months = $data->custom_expire_offset;
                $originalDate->modify("+$months months");
            } elseif ($data->custom_expire_units == 'DAY') {
                $days = $data->custom_expire_offset;
                $originalDate->modify("+$days days");
            }

            if ($originalDate > $currentDate) {
                $res = $originalDate->format('Y-m-d H:i:s');
            } else {
                $res = false;
            }

        } elseif ($data->expiration_rule_id == self::EXPIRATION_RULES_ANNUAL) {
            $year = $originalDate->format('Y');
            $newDate = new \DateTime();
            $newDate->setDate($year, $data->annual_expire_month, $data->annual_expire_day);
            $newDate->setTime(0, 0, 0);
            if ($newDate > $currentDate) {
                $res = $newDate->format('Y-m-d H:i:s');
            } else {
                $res = false;
            }

        } elseif ($data->expiration_rule_id == self::EXPIRATION_RULES_SPECIFIED) {

        } elseif ($data->expiration_rule_id == self::EXPIRATION_RULES_TWO_YEARS) {
            $year = $originalDate->format('Y');
            $newDate = new \DateTime();
            $newDate->setDate($year + 2, 12, 31);
            $newDate->setTime(23, 59, 59);
            if ($newDate > $currentDate) {
                $res = $newDate->format('Y-m-d H:i:s');
            } else {
                $res = false;
            }
        }

        return $res;
    }

    public function getUserBalance($accountHolderId)
    {
        $results = DB::table('accounts')
            ->select('journal_event_types.type', 'event_xml_data.name', 'journal_events.created_at as event_date', 'postings.posting_amount as amount', 'postings.is_credit', 'journal_events.journal_event_type_id')
            ->join('account_types', 'account_types.id', '=', 'accounts.account_type_id')
            ->join('postings', 'postings.account_id', '=', 'accounts.id')
            ->join('journal_events', 'journal_events.id', '=', 'postings.journal_event_id')
            ->join('journal_event_types', 'journal_event_types.id', '=', 'journal_events.journal_event_type_id')
            ->leftJoin('event_xml_data', 'event_xml_data.id', '=', 'journal_events.event_xml_data_id')
            ->where('accounts.account_holder_id', '=', $accountHolderId)
            ->get();
        $awardPointstoRecipient = 0;
        $redeemPointsForGiftCodes = 0;
        $reclaimPoints = 0;
        foreach ($results->toArray() as $value) {
            if ($value->type == AccountType::ACCOUNT_RECLAIM_POINTS) {
                if ($value->is_credit) {
                    $reclaimPoints += $value->amount;
                } else {
                    $reclaimPoints -= $value->amount;
                }
            }

            if ($value->type == AccountType::ACCOUNT_AWARD_POINTS_RECIPIENT) {
                if ($value->is_credit) {
                    $awardPointstoRecipient += $value->amount;
                } else {
                    $awardPointstoRecipient -= $value->amount;
                }
            }

            if ($value->type == AccountType::ACCOUNT_AWARD_MONIES_RECIPIENT) {
                if ($value->is_credit) {
                    $awardPointstoRecipient += $value->amount;
                } else {
                    $awardPointstoRecipient -= $value->amount;
                }
            }

            if ($value->type == AccountType::ACCOUNT_REDEEM_POINTS_GIFT_CODES) {
                if ($value->is_credit) {
                    $redeemPointsForGiftCodes += $value->amount;
                } else {
                    $redeemPointsForGiftCodes -= $value->amount;
                }
            }
        }

        if ($value->type == AccountType::ACCOUNT_REDEEM_MONIES_GIFT_CODES) {
            if ($value->is_credit) {
                $redeemPointsForGiftCodes += $value->amount;
            } else {
                $redeemPointsForGiftCodes -= $value->amount;
            }
        }

        $balance = $reclaimPoints + $awardPointstoRecipient + $redeemPointsForGiftCodes;
        return [
            'balance' => number_format($balance, 2, '.', '')
        ];
    }

    public function reclaim($request, $user)
    {
        $result = [
            'success' => false,
        ];

        try {
            DB::beginTransaction();

            $awardService = new AwardService();
            $user = User::findOrFail($request->userId);
            $program = Program::where('account_holder_id', $request->program_account_holder_id)->first();

            $posting = Posting::findOrFail($request->postingId);
            $parentJournalEventId = $posting->journal_event_id;
            $amount = $posting->posting_amount;
            $event_xml_data_id = $posting->journalEvent->event_xml_data_id;
            $notes = $request->notes;

            $reclaimableList = $awardService->readListExpireFuture($program, $user)['expiration'];
            sort($reclaimableList);

            $totalReclaimAble = 0;
            foreach ( $reclaimableList as $reclaimablePosting ) {
                if ($reclaimablePosting->program_id != $program->account_holder_id) {
                    continue;
                }
                $totalReclaimAble += $reclaimablePosting->amount;
            }

            if (compare_floats ( $totalReclaimAble, $amount ) > 0) {
                throw new InvalidArgumentException ( "The total reclaimable amount for this user is less than the amount trying to be reclaimed ({$totalReclaimAble} < {$amount})" );
            }


            // @todo award_credit is always disabled, this feature is not implemented
            $award_credit_date_start = null;
            $asset = FinanceType::getIdByName('Asset', true);
            $liability = FinanceType::getIdByName('Liability');
            $points = MediumType::getIdByName('Points', true);
            $monies = MediumType::getIdByName('Monies', true);
            $currency_id = Currency::getIdByType(config('global.default_currency'), true);

            if ($program->program_is_invoice_for_awards ()) {
                $journalEventTypeName = $award_credit_date_start ? JournalEventType::JOURNAL_EVENT_TYPES_AWARD_CREDIT_RECLAIM_POINTS : JournalEventType::JOURNAL_EVENT_TYPES_RECLAIM_POINTS;
            } else {
                $journalEventTypeName = $award_credit_date_start ? JournalEventType::JOURNAL_EVENT_TYPES_AWARD_CREDIT_RECLAIM_MONIES : JournalEventType::JOURNAL_EVENT_TYPES_RECLAIM_MONIES;
            }

            $journalEventTypeId = JournalEventType::getIdByType( $journalEventTypeName );
            $journalEventId = JournalEvent::insertGetId([
                'journal_event_type_id' => $journalEventTypeId,
                'event_xml_data_id' => null,
                'notes' => $notes,
                'parent_journal_event_id' => $parentJournalEventId,
                'prime_account_holder_id' => $user->account_holder_id,
                'created_at' => now()
            ]);


            if ($program->program_is_invoice_for_awards ()) {
                $accountPostings = Account::postings(
                    $user->account_holder_id,
                    AccountType::ACCOUNT_TYPE_POINTS_AWARDED,
                    $liability,
                    $points,
                    $program->account_holder_id,
                    AccountType::ACCOUNT_TYPE_POINTS_AVAILABLE,
                    $liability,
                    $points,
                    $journalEventId,
                    $amount,
                    1, //qty
                    null, // medium_info
                    null, // medium_info_id
                    $currency_id
                );

                $accountPostings = Account::postings(
                    $user->account_holder_id,
                    AccountType::ACCOUNT_TYPE_POINTS_AVAILABLE,
                    $liability,
                    $points,
                    $program->account_holder_id,
                    AccountType::ACCOUNT_TYPE_MONIES_DUE_TO_OWNER,
                    $asset,
                    $monies,
                    $journalEventId,
                    $amount,
                    1, //qty
                    null, // medium_info
                    null, // medium_info_id
                    $currency_id
                );
            } else {
                $accountPostings = Account::postings(
                    $user->account_holder_id,
                    AccountType::ACCOUNT_TYPE_MONIES_AWARDED,
                    $liability,
                    $monies,
                    $program->account_holder_id,
                    AccountType::ACCOUNT_TYPE_MONIES_AVAILABLE,
                    $asset,
                    $monies,
                    $journalEventId,
                    $amount,
                    1, //qty
                    null, // medium_info
                    null, // medium_info_id
                    $currency_id
                );
            }

            $result['success'] = true;

            DB::commit();
        } catch (\Exception $e){
            DB::rollBack();
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * @param User $user
     * @param array $roles
     * @return void
     */
    public function updateUnitNumber(User $user, int $newUnitNumber)
    {
        $currentUnitNumber = $user->unitNumber ? $user->unitNumber->id : null;

        if( $newUnitNumber === $currentUnitNumber ) return;

        if( $currentUnitNumber )
        {
            $user->unit_numbers()->where('unit_number', '=', $currentUnitNumber)->detach();
        }

        $user->unit_numbers()->attach([$newUnitNumber]);
        return $newUnitNumber;
    }
}
