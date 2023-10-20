<?php

namespace App\Services\reports;

use App\Models\AccountType;
use App\Models\JournalEventType;
use App\Models\User;
use DateTime;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ReportAwardSummaryReclaimsService extends ReportServiceAbstract
{

    /**
     * @inheritDoc
     */
    protected function getBaseQuery(): Builder
    {
        $query = DB::table('programs');
        $query->join('accounts', 'accounts.account_holder_id', '=', 'programs.account_holder_id');
        $query->join('account_types', 'account_types.id', '=', 'accounts.account_type_id');
        $query->join('postings', 'postings.account_id', '=', 'accounts.id');
        $query->join('journal_events', 'journal_events.id', '=', 'postings.journal_event_id');
        $query->join('journal_event_types', 'journal_event_types.id', '=', 'journal_events.journal_event_type_id');
        $query->join('postings as user_posting', 'user_posting.journal_event_id', '=', 'journal_events.id');
        $query->join('accounts as user_accounts', 'user_accounts.id', '=', 'user_posting.account_id');
        $query->join('users as recipient', 'recipient.account_holder_id', '=', 'user_accounts.account_holder_id');

        $query->selectRaw("
            coalesce(
                sum((case when (month(`postings`.`created_at`) = 1) then `postings`.`posting_amount` end)),
                0) AS `month1_value`,
            coalesce(
                sum((case when (month(`postings`.`created_at`) = 2) then `postings`.`posting_amount` end)),
                0) AS `month2_value`,
            coalesce(
                sum((case when (month(`postings`.`created_at`) = 3) then `postings`.`posting_amount` end)),
                0) AS `month3_value`,
            coalesce(
                sum((case when (month(`postings`.`created_at`) = 4) then `postings`.`posting_amount` end)),
                0) AS `month4_value`,
            coalesce(
                sum((case when (month(`postings`.`created_at`) = 5) then `postings`.`posting_amount` end)),
                0) AS `month5_value`,
            coalesce(
                sum((case when (month(`postings`.`created_at`) = 6) then `postings`.`posting_amount` end)),
                0) AS `month6_value`,
            coalesce(
                sum((case when (month(`postings`.`created_at`) = 7) then `postings`.`posting_amount` end)),
                0) AS `month7_value`,
            coalesce(
                sum((case when (month(`postings`.`created_at`) = 8) then `postings`.`posting_amount` end)),
                0) AS `month8_value`,
            coalesce(
                sum((case when (month(`postings`.`created_at`) = 9) then `postings`.`posting_amount` end)),
                0) AS `month9_value`,
            coalesce(
                sum((case when (month(`postings`.`created_at`) = 10) then `postings`.`posting_amount` end)),
                0) AS `month10_value`,
            coalesce(
                sum((case when (month(`postings`.`created_at`) = 11) then `postings`.`posting_amount` end)),
                0) AS `month11_value`,
            coalesce(
                sum((case when (month(`postings`.`created_at`) = 12) then `postings`.`posting_amount` end)),
                0) AS `month12_value`,
            coalesce(
                count((case when (month(`postings`.`created_at`) = 1) then `postings`.`journal_event_id` end)),
                0) AS `month1_count`,
            coalesce(
                count((case when (month(`postings`.`created_at`) = 2) then `postings`.`journal_event_id` end)),
                0) AS `month2_count`,
            coalesce(
                count((case when (month(`postings`.`created_at`) = 3) then `postings`.`journal_event_id` end)),
                0) AS `month3_count`,
            coalesce(
                count((case when (month(`postings`.`created_at`) = 4) then `postings`.`journal_event_id` end)),
                0) AS `month4_count`,
            coalesce(
                count((case when (month(`postings`.`created_at`) = 5) then `postings`.`journal_event_id` end)),
                0) AS `month5_count`,
            coalesce(
                count((case when (month(`postings`.`created_at`) = 6) then `postings`.`journal_event_id` end)),
                0) AS `month6_count`,
            coalesce(
                count((case when (month(`postings`.`created_at`) = 7) then `postings`.`journal_event_id` end)),
                0) AS `month7_count`,
            coalesce(
                count((case when (month(`postings`.`created_at`) = 8) then `postings`.`journal_event_id` end)),
                0) AS `month8_count`,
            coalesce(
                count((case when (month(`postings`.`created_at`) = 9) then `postings`.`journal_event_id` end)),
                0) AS `month9_count`,
            coalesce(
                count((case when (month(`postings`.`created_at`) = 10) then `postings`.`journal_event_id` end)),
                0) AS `month10_count`,
            coalesce(
                count((case when (month(`postings`.`created_at`) = 11) then `postings`.`journal_event_id` end)),
                0) AS `month11_count`,
            coalesce(
                count((case when (month(`postings`.`created_at`) = 12) then `postings`.`journal_event_id` end)),
                0) AS `month12_count`,
            `programs`.`account_holder_id` AS `program_id`,
            MAX(`programs`.`name`) AS `program_name`,
            `recipient`.`account_holder_id` AS `recipient_id`,
            MAX(`recipient`.`first_name`) AS `recipient_first_name`,
            MAX(`recipient`.`last_name`) AS `recipient_last_name`,
            YEAR(`postings`.`created_at`) AS `year`
        ");
        return $query;
    }

    /**
     * @inheritDoc
     */
    protected function setWhereFilters(Builder $query): Builder
    {
        $query->whereIn('account_types.name', [
            AccountType::ACCOUNT_TYPE_POINTS_AVAILABLE,
            AccountType::ACCOUNT_TYPE_MONIES_AVAILABLE,
        ]);
        $query->whereIn('journal_event_types.type', [
            JournalEventType::JOURNAL_EVENT_TYPES_RECLAIM_POINTS,
            JournalEventType::JOURNAL_EVENT_TYPES_RECLAIM_MONIES,
        ]);
        $query->whereRaw("YEAR(`postings`.`created_at`) = {$this->params[self::YEAR]}");
        $query->where('postings.is_credit', '=', true);
        $query->whereIn('programs.account_holder_id', $this->params[self::PROGRAMS]);
        return $query;
    }

    protected function setGroupBy(Builder $query): Builder
    {
        $query->groupBy(['postings.id', 'programs.id', 'recipient.account_holder_id', DB::raw('YEAR(postings.created_at)')]);
        return $query;
    }

}
