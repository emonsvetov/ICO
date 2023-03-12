<?php

namespace App\Services\reports;

use App\Models\AccountType;
use App\Models\JournalEventType;
use App\Models\MediumInfo;
use App\Models\Merchant;
use App\Models\OptimalValue;
use App\Models\Posting;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Expr\Cast\Object_;
use PhpParser\Node\Expr\PostInc;
use stdClass;

class ReportSumProgramsSupplierRedemptionService extends ReportServiceAbstract
{

    const FIELD_TOTAL_DOLLAR_VALUE_REDEEMED = 'total_dollar_value_redeemed';
    const FIELD_TOTAL_DOLLAR_VALUE_REBATED = 'total_dollar_value_rebated';

    protected function getBaseSql(): Builder
    {
        $query = DB::table('users');
        $query->addSelect(
            DB::raw("
                COALESCE(sum(postings.posting_amount), 0) as " . self::FIELD_TOTAL_DOLLAR_VALUE_REDEEMED . ",
                COALESCE(sum(program_posting.posting_amount), 0) as " . self::FIELD_TOTAL_DOLLAR_VALUE_REBATED . ",
                merchants.id as merchant_id,
                merchants.name as merchant_name,
                COUNT(journal_events.id) as count
            "),
        );

        $query->join('accounts', 'accounts.account_holder_id', '=', 'users.account_holder_id');
        $query->join('account_types', 'account_types.id', '=', 'accounts.account_type_id');
        $query->join('postings', 'postings.account_id', '=', 'accounts.id');
        $query->join('journal_events', 'journal_events.id', '=', 'postings.journal_event_id');
        $query->join('journal_event_types', 'journal_event_types.id', '=', 'journal_events.journal_event_type_id');
        $query->join('postings as merchant_posting', 'merchant_posting.journal_event_id', '=', 'journal_events.id');
        $query->join('accounts as merchant_accounts', 'merchant_accounts.id', '=', 'merchant_posting.account_id');
        $query->join('account_types as merchant_account_types', function ($join) {
            $join->on('merchant_account_types.id', '=', 'merchant_accounts.account_type_id');
            $join->on('merchant_account_types.name', '=',
                DB::raw("'" . AccountType::ACCOUNT_TYPE_GIFT_CODES_AVAILABLE . "'"));
        });
        $query->join('merchants', 'merchants.account_holder_id', '=', 'merchant_accounts.account_holder_id');
        $query->join('postings as program_posting', 'program_posting.journal_event_id', '=', 'journal_events.id');
        $query->join('accounts as program_accounts', 'program_accounts.id', '=', 'program_posting.account_id');
        $query->join('account_types as program_account_types', function ($join) {
            $join->on('program_account_types.id', '=', 'program_accounts.account_type_id');
            $join->on('program_account_types.name', '=', DB::raw("'" . AccountType::ACCOUNT_TYPE_MONIES_SHARED . "'"));
        });
        $query->leftJoin('medium_info', 'medium_info.id', '=', 'merchant_posting.medium_info_id');
        $query->join('programs', 'programs.account_holder_id', '=', 'program_accounts.account_holder_id');

        return $query;
    }

    /**
     * @inheritDoc
     */
    protected function setWhereFilters(Builder $query): Builder
    {
        $query->where(function ($q) {
            $q->where(function ($q2) {
                $q2->where('account_types.name', '=', AccountType::ACCOUNT_TYPE_MONIES_AWARDED)
                    ->where('journal_event_types.type', '=',
                        JournalEventType::JOURNAL_EVENT_TYPES_REDEEM_MONIES_FOR_GIFT_CODES);
            })
                ->orWhere(function ($q2) {
                    $q2->where('account_types.name', '=', AccountType::ACCOUNT_TYPE_POINTS_AWARDED)
                        ->where(function ($q3) {
                            $q3->where('journal_event_types.type', '=',
                                JournalEventType::JOURNAL_EVENT_TYPES_REDEEM_POINTS_FOR_GIFT_CODES)
                                ->orWhere('journal_event_types.type', '=',
                                    JournalEventType::JOURNAL_EVENT_TYPES_REDEEM_POINTS_FOR_INTERNATIONAL_SHOPPING);
                        });
                });
        });
        $query->where('postings.is_credit', '=', 0);
        $query->whereBetween('postings.created_at', [$this->params[self::DATE_FROM], $this->params[self::DATE_TO]]);
        $query->whereIn('programs.account_holder_id', $this->params[self::PROGRAMS]);

        return $query;
    }

    protected function setGroupBy(Builder $query): Builder
    {
        $query->groupBy(['merchants.id', 'merchants.name']);
        return $query;
    }

    protected function setOrderBy(Builder $query): Builder
    {
        if ($this->params[self::SQL_ORDER_BY]){
            $query->orderByRaw('`'.$this->params[self::SQL_ORDER_BY].'` DESC');
        }
        return $query;
    }

}


