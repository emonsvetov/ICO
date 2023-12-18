<?php

namespace App\Services\reports;

use App\Models\AccountType;
use App\Models\JournalEventType;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ReportPortfolioStatusReportNewService extends ReportServiceAbstract
{

    /**
     * @inheritDoc
     */
    protected function getBaseQuery(): Builder
    {
        return DB::table(function ($query) {
            $query->from('programs');
            $query->select([
                'programs.account_holder_id as program_id',
                'programs.name',
            ]);

            $query->addSelect(['count_users' => function ($query) {
                $query->from('users');
                $query->select(DB::raw("COUNT(DISTINCT users.account_holder_id)"));
                $userClassForSql = str_replace('\\', '\\\\\\\\', get_class(new User));
                $query->join('model_has_roles', function ($join) use ($userClassForSql) {
                    $join->on('model_has_roles.model_id', '=', 'users.id');
                    $join->on('model_has_roles.model_type', 'like', DB::raw("'" . $userClassForSql . "'"));
                });
                $query->join('program_user', function ($join) use ($userClassForSql) {
                    $join->on('program_user.user_id', '=', 'users.id');
                    $join->on('program_user.program_id', '=', DB::raw("programs.id"));
                });
                $query->join('roles', 'model_has_roles.role_id', '=', 'roles.id');
                $query->where('roles.name', 'LIKE', config('roles.participant'));
                $query->where('model_has_roles.program_id', '=', DB::raw('programs.id'));
                $query->whereBetween('users.created_at', [$this->params[self::DATE_FROM], $this->params[self::DATE_TO]]);
            }]);

            $query->addSelect(['count_active_user' => function ($query) {
                $query->from('users');
                $query->select(DB::raw("COUNT(DISTINCT users.account_holder_id)"));
                $userClassForSql = str_replace('\\', '\\\\\\\\', get_class(new User));
                $query->join('model_has_roles', function ($join) use ($userClassForSql) {
                    $join->on('model_has_roles.model_id', '=', 'users.id');
                    $join->on('model_has_roles.model_type', 'like', DB::raw("'" . $userClassForSql . "'"));
                });
                $query->join('program_user', function ($join) use ($userClassForSql) {
                    $join->on('program_user.user_id', '=', 'users.id');
                    $join->on('program_user.program_id', '=', DB::raw("programs.id"));
                });
                $query->join('roles', 'model_has_roles.role_id', '=', 'roles.id');
                $query->join('statuses', 'statuses.id', '=', 'users.user_status_id');
                $query->where('roles.name', 'LIKE', config('roles.participant'));
                $query->where('statuses.status', 'LIKE', config('global.user_status_active'));
                $query->where('model_has_roles.program_id', '=', DB::raw('programs.id'));
                $query->whereBetween('users.created_at', [$this->params[self::DATE_FROM], $this->params[self::DATE_TO]]);
            }]);

            $query->addSelect(['count_email' => function ($query) {
                $query->from('users');
                $query->select(DB::raw("COUNT(DISTINCT users.email)"));
                $userClassForSql = str_replace('\\', '\\\\\\\\', get_class(new User));
                $query->join('program_user', function ($join) use ($userClassForSql) {
                    $join->on('program_user.user_id', '=', 'users.id');
                    $join->on('program_user.program_id', '=', DB::raw("programs.id"));
                });
                $query->join('model_has_roles', function ($join) use ($userClassForSql) {
                    $join->on('model_has_roles.model_id', '=', 'users.id');
                    $join->on('model_has_roles.model_type', 'like', DB::raw("'" . $userClassForSql . "'"));
                });
                $query->join('roles', 'model_has_roles.role_id', '=', 'roles.id');
                $query->where('roles.name', 'LIKE', config('roles.participant'));
                $query->where('model_has_roles.program_id', '=', DB::raw('programs.id'));
                $query->whereBetween('users.created_at', [$this->params[self::DATE_FROM], $this->params[self::DATE_TO]]);
            }]);

            $query->addSelect(['count_award' => function ($query) {
                $query->from('users');
                $query->select(DB::raw("COUNT(*)"));

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
                $query->join('programs as p', 'p.account_holder_id', '=', 'program_accounts.account_holder_id');
                $query->leftJoin('event_xml_data', 'event_xml_data.id', '=', 'journal_events.event_xml_data_id');
                $query->leftJoin('users as awarder', 'awarder.account_holder_id', '=', 'event_xml_data.awarder_account_holder_id');
                // TODO: Award Levels
                // join `award_level` ON ((`award_level`.`program_account_holder_id` = `p`.`account_holder_id`)))
                // join `award_levels_has_users` ON (((`award_levels_has_users`.`users_id` = `recipient`.`account_holder_id`)
                // and (`award_levels_has_users`.`award_levels_id` = `award_level`.`id`))))

                $query->where(function ($q) {
                    $q->where('account_types.name', '=', AccountType::getTypePointsAwarded())
                        ->orWhere('account_types.name', '=', AccountType::getTypeMoniesAwarded());
                });
                $query->where(function ($q) {
                    $q->where('journal_event_types.type', '=', JournalEventType::JOURNAL_EVENT_TYPES_AWARD_POINTS_TO_RECIPIENT)
                        ->orWhere('journal_event_types.type', '=', JournalEventType::JOURNAL_EVENT_TYPES_AWARD_MONIES_TO_RECIPIENT);
                });
                $query->where('postings.is_credit', '=', 1);
                $query->whereBetween('postings.created_at', [$this->params[self::DATE_FROM], $this->params[self::DATE_TO]]);
                $query->where('p.id', '=', DB::raw('programs.id'));
            }]);

            $query->addSelect(['sum_posting_amount' => function ($query) {
                $query->from('users');
                $query->select(DB::raw("SUM(postings.posting_amount)"));

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
                $query->join('programs as p', 'p.account_holder_id', '=', 'program_accounts.account_holder_id');
                $query->leftJoin('event_xml_data', 'event_xml_data.id', '=', 'journal_events.event_xml_data_id');
                $query->leftJoin('users as awarder', 'awarder.account_holder_id', '=', 'event_xml_data.awarder_account_holder_id');
                // TODO: Award Levels
                // join `award_level` ON ((`award_level`.`program_account_holder_id` = `p`.`account_holder_id`)))
                // join `award_levels_has_users` ON (((`award_levels_has_users`.`users_id` = `recipient`.`account_holder_id`)
                // and (`award_levels_has_users`.`award_levels_id` = `award_level`.`id`))))

                $query->where(function ($q) {
                    $q->where('account_types.name', '=', AccountType::getTypePointsAwarded())
                        ->orWhere('account_types.name', '=', AccountType::getTypeMoniesAwarded());
                });
                $query->where(function ($q) {
                    $q->where('journal_event_types.type', '=', JournalEventType::JOURNAL_EVENT_TYPES_AWARD_POINTS_TO_RECIPIENT)
                        ->orWhere('journal_event_types.type', '=', JournalEventType::JOURNAL_EVENT_TYPES_AWARD_MONIES_TO_RECIPIENT);
                });
                $query->where('postings.is_credit', '=', 1);
                $query->whereBetween('postings.created_at', [$this->params[self::DATE_FROM], $this->params[self::DATE_TO]]);
                $query->where('p.id', '=', DB::raw('programs.id'));
            }]);

            $query->addSelect(['avg_posting_amount' => function ($query) {
                $query->from('users');
                $query->select(DB::raw("AVG(postings.posting_amount)"));

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
                $query->join('programs as p', 'p.account_holder_id', '=', 'program_accounts.account_holder_id');
                $query->leftJoin('event_xml_data', 'event_xml_data.id', '=', 'journal_events.event_xml_data_id');
                $query->leftJoin('users as awarder', 'awarder.account_holder_id', '=', 'event_xml_data.awarder_account_holder_id');
                // TODO: Award Levels
                // join `award_level` ON ((`award_level`.`program_account_holder_id` = `p`.`account_holder_id`)))
                // join `award_levels_has_users` ON (((`award_levels_has_users`.`users_id` = `recipient`.`account_holder_id`)
                // and (`award_levels_has_users`.`award_levels_id` = `award_level`.`id`))))

                $query->where(function ($q) {
                    $q->where('account_types.name', '=', AccountType::getTypePointsAwarded())
                        ->orWhere('account_types.name', '=', AccountType::getTypeMoniesAwarded());
                });
                $query->where(function ($q) {
                    $q->where('journal_event_types.type', '=', JournalEventType::JOURNAL_EVENT_TYPES_AWARD_POINTS_TO_RECIPIENT)
                        ->orWhere('journal_event_types.type', '=', JournalEventType::JOURNAL_EVENT_TYPES_AWARD_MONIES_TO_RECIPIENT);
                });
                $query->where('postings.is_credit', '=', 1);
                $query->whereBetween('postings.created_at', [$this->params[self::DATE_FROM], $this->params[self::DATE_TO]]);
                $query->where('p.id', '=', DB::raw('programs.id'));
            }]);

            $subQuery1 = DB::table('accounts');
            $subQuery1->select(DB::raw("sum(postings.posting_amount * postings.qty)"));
            $subQuery1->join('account_types', 'account_types.id', '=', 'accounts.account_type_id');
            $subQuery1->join('postings', 'postings.account_id', '=', 'accounts.id');
            $subQuery1->where('accounts.account_holder_id', '=', DB::raw('programs.account_holder_id'));
            $subQuery1->where('account_types.name', '=', AccountType::ACCOUNT_TYPE_MONIES_AVAILABLE);
            $subQuery1->where('postings.is_credit', '=', 1);
            $subQuery1->whereBetween('postings.created_at', [$this->params[self::DATE_FROM], $this->params[self::DATE_TO]]);

            $subQuery2 = DB::table('accounts');
            $subQuery2->select(DB::raw("sum(postings.posting_amount * postings.qty)"));
            $subQuery2->join('account_types', 'account_types.id', '=', 'accounts.account_type_id');
            $subQuery2->join('postings', 'postings.account_id', '=', 'accounts.id');
            $subQuery2->where('accounts.account_holder_id', '=', DB::raw('programs.account_holder_id'));
            $subQuery2->where('account_types.name', '=', AccountType::ACCOUNT_TYPE_MONIES_AVAILABLE);
            $subQuery2->where('postings.is_credit', '=', 0);
            $subQuery2->whereBetween('postings.created_at', [$this->params[self::DATE_FROM], $this->params[self::DATE_TO]]);
            $query->addSelect([
                DB::raw("
                (CASE
                    WHEN max(programs.invoice_for_awards) = 0 THEN ''
                    ELSE (
                        ifnull(({$subQuery1->toSql()}), 0) - ifnull(({$subQuery2->toSql()}), 0)
                        )
                END) AS deposit_balance")
            ]);
            $query->mergeBindings($subQuery1);
            $query->mergeBindings($subQuery2);
            $query->leftJoin('accounts', 'accounts.account_holder_id', '=', 'programs.account_holder_id');
            $query->where('programs.deactivate_account', '=', 0)
                ->orWhereNull('programs.deactivate_account');
            $query->groupBy('programs.account_holder_id');
            $query->groupBy('name');
            $query->groupBy('programs.id');
            $query->orderBy('programs.name');
        }, 'subQueryAll')
            ->select(
                DB::raw("
                program_id,
                name,
                count_users,
                count_active_user,
                count_email,
                count_award,
                sum_posting_amount,
                avg_posting_amount,
                deposit_balance,
                (CASE
                    WHEN count_users > 0 THEN cast((count_email/count_users*100) as decimal(9,1))
                    ELSE 0
                END) as 'percent_participant',
                (CASE
                    WHEN count_active_user > 0 THEN cast((count_email/count_active_user*100) as decimal(9,1))
                    ELSE 0
                END) as 'percent_active_participant'
                ")
            );

        return $query;
    }

    /**
     * Calculate full data
     *
     * @return array
     */
    protected function calc(): array
    {
        $this->table = [];
        $query = $this->getBaseQuery();
        $this->table['total'] = count($query->get()->toArray());
        $query = $this->setLimit($query);
        $this->table['data'] = $query->get()->toArray();
        return $this->table;
    }

    /**
     * @inheritDoc
     */
    protected function setWhereFilters(Builder $query): Builder
    {
        return $query;
    }

    /**
     * @inheritDoc
     */
    protected function setGroupBy(Builder $query): Builder
    {
        return $query;
    }

    /**
     * @inheritDoc
     */
    protected function setOrderBy(Builder $query): Builder
    {
        return $query;
    }

    protected function getReportForCSV(): array
    {
        $this->isExport = true;
        $this->params[self::SQL_LIMIT] = null;
        $this->params[self::SQL_OFFSET] = null;
        $data = $this->getTable();
        $data['headers'] = $this->getCsvHeaders();
        return $data;
    }

    public function getCsvHeaders(): array
    {
        return [
            [
                'label' => 'Program Name',
                'key' => 'name'
            ],
            [
                'label' => '# of Participants',
                'key' => 'count_users'
            ],
            [
                'label' => '# of Units with Participants',
                'key' => 'count_email'
            ],
            [
                'label' => '% of Units with Participants',
                'key' => 'percent_participant'
            ],
            [
                'label' => '# Activated',
                'key' => 'count_active_user'
            ],
            [
                'label' => '% Activated',
                'key' => 'percent_active_participant'
            ],
            [
                'label' => '# Awards',
                'key' => 'count_award'
            ],
            [
                'label' => '$ Value of Awards',
                'key' => 'sum_posting_amount'
            ],
            [
                'label' => 'Avg $ Value of Awards',
                'key' => 'avg_posting_amount'
            ],
            [
                'label' => 'Deposit Balance',
                'key' => 'deposit_balance'
            ],
        ];
    }

}
