<?php

namespace App\Services\reports;

use App\Models\Account;
use App\Models\AccountType;
use App\Models\JournalEventType;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ReportHelper
{

    /**
     * Count Participants By User Statuses
     * @param array $userStatuses
     * @param string $dateBegin
     * @param string $dateEnd
     * @return array
     */
    public function countParticipantsByUserStatuses(array $userStatuses, string $dateBegin, string $dateEnd): array
    {
        $userClassForSql = str_replace('\\', '\\\\\\\\', get_class(new User));
        $query = User::select(
            'programs.account_holder_id as program_id',
            DB::raw("COUNT(DISTINCT `users`.account_holder_id) as count")
        );
        $query->join('model_has_roles', function ($join) use ($userClassForSql) {
            $join->on('model_has_roles.model_id', '=', 'users.id');
            $join->on('model_has_roles.model_type', 'like', DB::raw("'" . $userClassForSql . "'"));
        });
        $query->join('roles', 'model_has_roles.role_id', '=', 'roles.id');
        $query->join('statuses', 'statuses.id', '=', 'users.user_status_id');
        $query->join('programs', 'programs.id', '=', 'model_has_roles.program_id');

        $query->where('roles.name', 'LIKE', config('roles.participant'));
        $query->where('users.created_at', '>=', $dateBegin);
        $query->where('users.created_at', '<=', $dateEnd);
        $query->whereIn('statuses.status', $userStatuses);
        $query->groupBy('model_has_roles.program_id');

        return $query->get()->pluck('count', 'program_id',)->toArray();
    }

    /**
     * Awards Audit
     * @param array $programs
     * @param string $dateBegin
     * @param string $dateEnd
     * @param array $args
     * @return Collection
     */
    public function awardsAudit(array $programs, string $dateBegin, string $dateEnd, array $args = []): Collection
    {
        $total = $args['total'] ?? null;
        $p2p = $args['p2p'] ?? null;
        $group = $args['group'] ?? null;
        $order = $args['order'] ?? null;
        $limit = $args['limit'] ?? null;
        $groupBy = null;
        if ($total) {
            $query = User::select(
                DB::raw('SUM(postings.posting_amount) as total'),
                'programs.account_holder_id',
                DB::raw('COUNT(journal_events.id) as count'),
            );
            $groupBy = 'programs.account_holder_id';
        } elseif ($group === 'event_name') {
            $query = User::select(
                DB::raw('SUM(postings.posting_amount) as total'),
                'event_xml_data.name as event_name',
                DB::raw('COUNT(journal_events.id) as count'),
            );
            $groupBy = 'event_xml_data.name';
        } elseif ($group === 'date') {
            $query = User::select(
                DB::raw('SUM(postings.posting_amount) as total'),
                DB::raw("DATE(`postings`.`created_at`) as `date`"),
                DB::raw('COUNT(journal_events.id) as count'),
            );
            $groupBy = 'date';
        } elseif ($group === 'month') {
            $query = User::select(
                DB::raw('SUM(postings.posting_amount) as total'),
                DB::raw("MONTH(`postings`.`created_at`) as `month`"),
                DB::raw('COUNT(journal_events.id) as count'),
            );
            $groupBy = 'month';
        } else {
            $query = User::select(
                'users.account_holder_id AS recipient_id',
                'users.first_name AS recipient_first_name',
                'users.last_name AS recipient_last_name',
                'users.organization_id AS recipient_organization_id',
                'programs.account_holder_id AS program_id',
                'programs.name AS program_name',
                DB::raw("
                    if(
                        (`postings`.`posting_amount` IS NOT NULL),
                        `postings`.`posting_amount`,
                        0
                       ) AS `dollar_value`
                "),
                'postings.created_at AS posting_timestamp',
                DB::raw("DATE(`postings`.`created_at`) as `date`"),
                DB::raw("MONTH(`postings`.`created_at`) as `month`"),
                DB::raw("YEAR(`postings`.`created_at`) as `year`"),
                DB::raw("
                    if((`postings`.`posting_amount` is not null),
                        (`postings`.`posting_amount` * `programs`.`factor_valuation`),
                        0) AS `points`
                "),
                "programs.factor_valuation",
                "event_xml_data.id AS event_xml_data_id",
                "event_xml_data.name AS event_name",
                "event_xml_data.referrer",
                "event_xml_data.notes",
                "event_xml_data.notification_body",
                "event_xml_data.xml",
                "awarder.account_holder_id AS awarder_id",
                "awarder.first_name AS awarder_first_name",
                "awarder.last_name AS awarder_last_name",
                "account_types.name AS account_type",
                "journal_events.id AS journal_event_id",
                "journal_event_types.type AS journal_event_type",
                DB::raw("
                    if((program_posting.posting_amount is not null),
                    'program_posting.posting_amount',0) AS `transaction_fee`
                "),
            );
        }

        $query->join('accounts', 'accounts.account_holder_id', '=', 'users.account_holder_id');
        $query->join('account_types', 'account_types.id', '=', 'accounts.account_type_id');
        $query->join('postings', 'postings.account_id', '=', 'accounts.id');
        $query->join('journal_events', 'journal_events.id', '=', 'postings.journal_event_id');
        $query->join('journal_event_types', 'journal_event_types.id', '=', 'journal_events.journal_event_type_id');

        $query->join('postings as program_posting', 'program_posting.journal_event_id', '=', 'journal_events.id');
        $query->join('accounts as program_accounts', 'program_accounts.id', '=', 'program_posting.account_id');
        $query->join('account_types as program_account_types', function ($join) {
            $join->on('program_account_types.id', '=', 'program_accounts.account_type_id');
            $join->on("program_account_types.name", "=", DB::raw("'" . AccountType::ACCOUNT_TYPE_MONIES_FEES . "'"));
        });
        $query->leftJoin('programs', 'programs.account_holder_id', '=', 'program_accounts.account_holder_id');

        $query->leftJoin('event_xml_data', 'event_xml_data.id', '=', 'journal_events.event_xml_data_id');
        $query->leftJoin('users as awarder', 'awarder.account_holder_id', '=',
            'event_xml_data.awarder_account_holder_id');

        if ($p2p){
            $query->where(function ($q) {
                $q->where('account_types.name', '=', AccountType::ACCOUNT_TYPE_PEER2PEER_POINTS)
                    ->orWhere('account_types.name', '=', AccountType::ACCOUNT_TYPE_PEER2PEER_MONIES);
            });
            $query->where('postings.is_credit', '=', 0);

        } else {
            $query->where(function ($q) {
                $q->where('account_types.name', '=', AccountType::getTypePointsAwarded())
                    ->orWhere('account_types.name', '=', AccountType::getTypeMoniesAwarded());
            });
            $query->where('postings.is_credit', '=', true);
        }
        $query->where(function ($q) {
            $q->where('journal_event_types.type', '=', JournalEventType::JOURNAL_EVENT_TYPES_AWARD_POINTS_TO_RECIPIENT)
                ->orWhere('journal_event_types.type', '=',
                    JournalEventType::JOURNAL_EVENT_TYPES_AWARD_MONIES_TO_RECIPIENT);
        });
        $query->whereBetween('postings.created_at', [$dateBegin, $dateEnd]);
        $query->whereIn('programs.account_holder_id', $programs);

        if ($groupBy) {
            $query->groupBy($groupBy);
            if ($order) {
                $query->orderBy($order, 'DESC');
            }
        }
        if ($limit) {
            $query->limit($limit);
        }

        return $query->get();
    }

    /**
     * Reclaims Audit
     * @param array $programs
     * @param string $dateBegin
     * @param string $dateEnd
     * @param array $args
     * @return Collection
     */
    public function reclaimsAudit(array $programs, string $dateBegin, string $dateEnd, array $args = []): Collection
    {
        $total = $args['total'] ?? null;
        $groupBy = null;
        if ($total) {
            $query = User::select(
                DB::raw('SUM(postings.posting_amount) as total'),
                'programs.account_holder_id',
                DB::raw('COUNT(journal_events.id) as count'),
            );
            $groupBy = 'programs.account_holder_id';
        } else {
            $query = User::select(
                'users.account_holder_id AS recipient_id',
                'users.first_name AS recipient_first_name',
                'users.last_name AS recipient_last_name',
                'users.organization_id AS recipient_organization_id',
                'programs.account_holder_id AS program_id',
                'programs.name AS program_name',
                DB::raw("
                    if(
                        (`postings`.`posting_amount` IS NOT NULL),
                        `postings`.`posting_amount`,
                        0
                       ) AS `dollar_value`
                "),
                'postings.created_at AS posting_timestamp',
                DB::raw("DATE(`postings`.`created_at`) as `date`"),
                DB::raw("MONTH(`postings`.`created_at`) as `month`"),
                DB::raw("YEAR(`postings`.`created_at`) as `year`"),
                DB::raw("
                    if((`postings`.`posting_amount` is not null),
                        (`postings`.`posting_amount` * `programs`.`factor_valuation`),
                        0) AS `points`
                "),
                "programs.factor_valuation",
                "event_xml_data.id AS event_xml_data_id",
                "event_xml_data.name AS event_name",
                "event_xml_data.referrer",
                "event_xml_data.notes",
                "event_xml_data.notification_body",
                "event_xml_data.xml",
                "awarder.account_holder_id AS awarder_id",
                "awarder.first_name AS awarder_first_name",
                "awarder.last_name AS awarder_last_name",
                "account_types.name AS account_type",
                "journal_events.id AS journal_event_id",
                "journal_event_types.type AS journal_event_type",
                DB::raw("
                    if((program_posting.posting_amount is not null),
                    'program_posting.posting_amount',0) AS `transaction_fee`
                "),
            );
        }

        $query->join('accounts', 'accounts.account_holder_id', '=', 'users.account_holder_id');
        $query->join('account_types', 'account_types.id', '=', 'accounts.account_type_id');
        $query->join('postings', 'postings.account_id', '=', 'accounts.id');
        $query->join('journal_events', 'journal_events.id', '=', 'postings.journal_event_id');
        $query->join('journal_event_types', 'journal_event_types.id', '=', 'journal_events.journal_event_type_id');

        $query->leftJoin('postings as program_posting', 'program_posting.journal_event_id', '=', 'journal_events.id');
        $query->leftJoin('accounts as program_accounts', 'program_accounts.id', '=', 'program_posting.account_id');
        $query->leftJoin('account_types as program_account_types', function ($join) {
            $join->on('program_account_types.id', '=', 'program_accounts.account_type_id');
            $join->on("program_account_types.name", "=",
                DB::raw("'" . AccountType::ACCOUNT_TYPE_MONIES_DUE_TO_OWNER . "'"))
                ->orOn("program_account_types.name", "=",
                    DB::raw("'" . AccountType::ACCOUNT_TYPE_MONIES_AVAILABLE . "'"));
        });
        $query->leftJoin('programs', 'programs.account_holder_id', '=', 'program_accounts.account_holder_id');

        $query->leftJoin('event_xml_data', 'event_xml_data.id', '=', 'journal_events.event_xml_data_id');
        $query->leftJoin('users as awarder', 'awarder.account_holder_id', '=',
            'event_xml_data.awarder_account_holder_id');

        $query->where(function ($q) {
            $q->where('account_types.name', '=', AccountType::getTypePointsAwarded())
                ->orWhere('account_types.name', '=', AccountType::getTypeMoniesAwarded());
        });
        $query->where(function ($q) {
            $q->where('journal_event_types.type', '=', JournalEventType::JOURNAL_EVENT_TYPES_RECLAIM_POINTS)
                ->orWhere('journal_event_types.type', '=', JournalEventType::JOURNAL_EVENT_TYPES_RECLAIM_MONIES);
        });
        $query->whereBetween('postings.created_at', [$dateBegin, $dateEnd]);
        $query->where('postings.is_credit', '=', false);
        $query->whereIn('programs.account_holder_id', $programs);

        if ($groupBy) {
            $query->groupBy($groupBy);
        }

        return $query->get();
    }


    public function sumPostsByAccountAndJournalEventAndCredit(
        string $dateBegin,
        string $dateEnd,
        array $args = []
    ): array {
        $accountTypes = $args['accountTypes'] ?? null;
        $journalEventTypes = $args['journalEventTypes'] ?? null;
        $isCredit = $args['isCredit'] ?? false;

        $query = Account::select(
            DB::raw('COALESCE(SUM(postings.posting_amount * postings.qty), 0) AS value'),
            DB::raw('journal_event_types.type as journal_event_type'),
            'accounts.account_holder_id',
            'account_types.name as account_type_name',
        );
        $query->join('account_types', 'account_types.id', '=', 'accounts.account_type_id');
        $query->join('postings', 'postings.account_id', '=', 'accounts.id');
        $query->join('journal_events', 'journal_events.id', '=', 'postings.journal_event_id');
        $query->join('journal_event_types', 'journal_event_types.id', '=', 'journal_events.journal_event_type_id');

        $query->whereBetween('postings.created_at', [$dateBegin, $dateEnd]);
        $query->where('postings.is_credit', '=', (bool)$isCredit);
        if ($accountTypes) {
            $query->whereIn('account_types.name', $accountTypes);
        }
        if ($journalEventTypes) {
//            $query->whereIn('journal_event_types.type', $journalEventTypes);
        }

        $query->groupBy('accounts.account_holder_id');
        $query->groupBy('account_types.name');
        $query->groupBy('journal_event_types.type');

        $result = $query->get();
        $table = [];
        foreach ($result as $row) {
            $table[$row->account_holder_id][$row->account_type_name][$row->journal_event_type] = $row->value;
        }

        return $table;
    }

}
