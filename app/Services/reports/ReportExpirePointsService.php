<?php

namespace App\Services\reports;

use App\Models\AccountType;
use App\Models\JournalEventType;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ReportExpirePointsService extends ReportServiceAbstract
{
    const START_DATE_FIELD = 'posting_date';

    /**
     * @inheritDoc
     */
    protected function getBaseQuery(): Builder
    {
        $subQuery2 = DB::table(function ($subQuery) {
            $start_date_field = self::START_DATE_FIELD;
            $userClassForSql = str_replace('\\', '\\\\\\\\', get_class(new User));

            $subQuery->from('programs');
            $subQuery->join('expiration_rules', 'expiration_rules.id', '=', 'programs.expiration_rule_id');
            $subQuery->join('accounts', 'accounts.account_holder_id', '=', 'programs.account_holder_id');
            $subQuery->join('account_types', 'account_types.id', '=', 'accounts.account_type_id');
            $subQuery->join('postings', 'postings.account_id', '=', 'accounts.id');
            $subQuery->join('journal_events', 'journal_events.id', '=', 'postings.journal_event_id');
            $subQuery->join('journal_event_types', 'journal_event_types.id', '=', 'journal_events.journal_event_type_id');
            $subQuery->join('postings as user_posting', 'user_posting.journal_event_id', '=', 'journal_events.id');
            $subQuery->join('accounts as user_accounts', 'user_accounts.id', '=', 'user_posting.account_id');
            $subQuery->join('users', 'users.account_holder_id', '=', 'user_accounts.account_holder_id');
            $subQuery->join('model_has_roles', function ($join) use ($userClassForSql) {
                $join->on('model_has_roles.model_id', '=', 'users.id');
                $join->on('model_has_roles.model_type', 'like', DB::raw("'" . $userClassForSql . "'"));
            });
            $subQuery->join('roles', 'model_has_roles.role_id', '=', 'roles.id');
            $subQuery->join('statuses', 'statuses.id', '=', 'users.user_status_id');

            $subQuery->selectRaw("
                `programs`.id as program_id
                ,`programs`.parent_id as program_parent_id
                ,`programs`.name as program_name
                , `users`.id as user_id
                , `users`.first_name as participant_first_name
                , `users`.last_name as participant_last_name
                , `users`.email as participant_email
                , cast(`postings`.`created_at` as date) AS `{$start_date_field}`
                ,`account_types`.`name` AS `account_type_name`
                ,`postings`.`is_credit` AS `is_credit`
                ,sum((`postings`.`posting_amount` * `postings`.`qty`)) AS `amount`
                ,`programs`.factor_valuation
                ,`users`.account_holder_id as user_account_holder_id

                #,`programs`.name as program_name
                ,`expiration_rules`.name as 'expiration_name'
                ,`expiration_rules`.expire_offset
                ,CASE
                    WHEN (`expiration_rules`.name IN ('12 Months', '9 Months', '6 Months', '3 Months'))
                    THEN
                        CASE `expiration_rules`.expire_units
                            WHEN 'DAY' THEN date_add(`postings`.created_at, INTERVAL `expiration_rules`.expire_offset DAY)
                            WHEN 'MONTH' THEN date_add(`postings`.created_at, INTERVAL `expiration_rules`.expire_offset MONTH)
                        END
                END as 'end_date'
                , date_add(date_format(NOW(), '%Y-12-31'),interval 1 day) as 'end_year'
                #, `users`.first_name as user_name
            ");

            $subQuery->where('roles.name', 'LIKE', config('roles.participant'));
            $subQuery->where('account_types.name', 'NOT LIKE', '');
            $subQuery->whereIn('programs.account_holder_id', $this->params[self::PROGRAMS]);
            $subQuery->groupBy(DB::raw('
                program_id, user_id, user_account_holder_id, is_credit, expiration_name, account_type_name,
                cast(`postings`.`created_at` as date), expire_offset, end_date, end_year, program_name, program_parent_id,
                participant_first_name, participant_last_name, participant_email
            '));
        }, 'subQuery')
            ->selectRaw("
                user_id,
                user_account_holder_id,
                program_id,
                program_parent_id,
                program_name,
                participant_first_name,
                participant_last_name,
                participant_email,
                SUM(IF(is_credit = 1, amount, 0)) AS total_credit,
                SUM(IF(is_credit = 0, amount, 0)) AS total_debit,
                SUM(IF(is_credit = 1 AND end_year >= end_date, amount, 0)) AS total_expiring_points,
                factor_valuation,
                end_date as 'expire_date'
                ,(
                    select
                        round(sum(posts.posting_amount * posts.qty),2) as total_posting_amount
                    from
                        accounts a
                        join account_types at on (at.id = a.account_type_id)
                        join postings posts on (posts.account_id = a.id)
                        join journal_events je on (je.id = posts.journal_event_id)
                        join journal_event_types jet on (jet.id = je.journal_event_type_id)
                    where
                        a.account_holder_id = user_account_holder_id
                        and posts.is_credit = 0
                        and at.name in (
                        '".AccountType::ACCOUNT_TYPE_POINTS_AWARDED."',
                        '".AccountType::ACCOUNT_TYPE_MONIES_AWARDED."')
                        and jet.type in (
                        '".JournalEventType::JOURNAL_EVENT_TYPES_REDEEM_POINTS_FOR_GIFT_CODES."',
                        '".JournalEventType::JOURNAL_EVENT_TYPES_REDEEM_POINTS_FOR_INTERNATIONAL_SHOPPING."',
                        '".JournalEventType::JOURNAL_EVENT_TYPES_REDEEM_MONIES_FOR_GIFT_CODES."')
                ) as redeemed
            ")
            ->groupBy(['user_id', 'user_account_holder_id', 'program_id', 'program_parent_id', 'program_name',
                'participant_first_name', 'participant_last_name', 'participant_email']);

        $query = DB::table(DB::raw("({$subQuery2->toSql()}) as sub2"));
        $query->selectRaw("
            user_id,
            user_account_holder_id,
            program_id,
            program_parent_id,
            program_name,
            CONCAT(LEFT(participant_first_name, 1), '.', LEFT(participant_last_name, 1), '.') as participant,
            CONCAT(LEFT(participant_email, 4), '*******', right(participant_email, 4), '.') as participant_email,
            total_debit,
            total_credit,
            (total_debit - total_credit) * factor_valuation as 'balance_without_redeemed',
            (total_debit - total_credit - IF(redeemed = 1, redeemed, 0)) * factor_valuation as 'balance',
            total_expiring_points,
            total_expiring_points * factor_valuation as 'amount_expiring',
            expire_date,
            IF(redeemed = 1, redeemed, 0) * factor_valuation as redeemed
        ");

        $query->whereRaw("
            total_debit - total_credit > 0
	        AND total_debit - total_expiring_points > 0
	        AND total_expiring_points > 0
        ");
        $query->mergeBindings($subQuery2);

        return $query;
    }

    public function getCsvHeaders(): array
    {
        return [
            [
                'label' => 'Program ID',
                'key' => 'program_id'
            ],
            [
                'label' => 'Program Parent',
                'key' => 'program_parent_id'
            ],
            [
                'label' => 'Program Name',
                'key' => 'program_name'
            ],
            [
                'label' => 'Participant',
                'key' => 'participant'
            ],
            [
                'label' => 'Participant Email',
                'key' => 'participant_email'
            ],
            [
                'label' => 'Expiring Date',
                'key' => 'expire_date'
            ],
            [
                'label' => 'Amount Expiring',
                'key' => 'amount_expiring'
            ],
            [
                'label' => 'Current Balance',
                'key' => 'balance'
            ],
        ];
    }

}
