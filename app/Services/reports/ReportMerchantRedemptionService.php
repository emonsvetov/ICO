<?php

namespace App\Services\reports;

use App\Models\AccountType;
use App\Models\JournalEventType;
use App\Models\Program;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use stdClass;

class ReportMerchantRedemptionService extends ReportServiceAbstract
{
    const FIELD_TOTAL_DOLLAR_VALUE_REDEEMED = 'total_dollar_value_redeemed';
    const FIELD_TOTAL_DOLLAR_VALUE_REBATED = 'total_dollar_value_rebated';

    /**
     * @inheritDoc
     */
    protected function getBaseQuery(): Builder
    {
        $query = DB::table('users');
        $query->join('accounts', 'accounts.account_holder_id', '=', 'users.account_holder_id');
        $query->join('account_types', 'account_types.id', '=', 'accounts.account_type_id');
        $query->join('postings', 'postings.account_id', '=', 'accounts.id');
        $query->join('journal_events', 'journal_events.id', '=', 'postings.journal_event_id');
        $query->join('journal_event_types', 'journal_event_types.id', '=', 'journal_events.journal_event_type_id');
        $query->join('postings as merchant_posting', 'merchant_posting.journal_event_id', '=', 'journal_events.id');
        $query->join('accounts as merchant_accounts', 'merchant_accounts.id', '=', 'merchant_posting.account_id');
        $query->join('account_types as merchant_account_types', function ($join) {
            $join->on('merchant_account_types.id', '=', 'merchant_accounts.account_type_id');
            $join->on('merchant_account_types.name', '=', DB::raw("'" . AccountType::ACCOUNT_TYPE_GIFT_CODES_AVAILABLE . "'"));
        });
        $query->join('merchants', 'merchants.account_holder_id', '=', 'merchant_accounts.account_holder_id');

        $query->join('postings as program_posting', 'program_posting.journal_event_id', '=', 'journal_events.id');
        $query->join('accounts as program_accounts', 'program_accounts.id', '=', 'program_posting.account_id');
        $query->join('account_types as program_account_types', function ($join) {
            $join->on('program_account_types.id', '=', 'program_accounts.account_type_id');
            $join->on('program_account_types.name', '=', DB::raw("'" . AccountType::ACCOUNT_TYPE_MONIES_SHARED . "'"));
        });
        $query->join('programs', 'programs.account_holder_id', '=', 'program_accounts.account_holder_id');
        $query->leftJoin('medium_info', 'medium_info.id', '=', 'merchant_posting.medium_info_id');

        $query->addSelect(
            DB::raw("
                MONTH(postings.created_at) as " . self::FIELD_MONTH . ",
                COALESCE(sum(postings.posting_amount), 0) as " . self::FIELD_TOTAL_DOLLAR_VALUE_REDEEMED . ",
                COALESCE(sum(program_posting.posting_amount), 0) as " . self::FIELD_TOTAL_DOLLAR_VALUE_REBATED . ",
                merchants.account_holder_id as merchant_id,
                merchants.name as merchant_name
            "),
        );
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
        $query->whereBetween('postings.created_at', [$this->params[self::DATE_BEGIN], $this->params[self::DATE_END]]);
        $query->whereIn('programs.account_holder_id', $this->params[self::PROGRAMS]);

        return $query;
    }

    protected function setGroupBy(Builder $query): Builder
    {
        $query->groupBy(['merchants.account_holder_id', 'merchants.name']);
        return $query;
    }

    public function getTable(): array
    {
        $table = [];
        $this->params[self::DATE_BEGIN] = $this->params[self::YEAR] . '-01-01 00:00:00';
        $this->params[self::DATE_END] = $this->params[self::YEAR] . '-12-31 23:59:59';

        if (empty($this->table)) {
            $this->calc();
        }

        if (count($this->table) > 0) {
            foreach ($this->table as $row) {
                if (isset ($table[$row->merchant_id])) {
                    continue;
                }
                $rowObj = new stdClass ();
                $rowObj->merchant_id = $row->merchant_id;
                $rowObj->merchant_name = $row->merchant_name;
                for ($i = 1; $i <= 13; ++$i) {
                    $rowObj->{'month' . $i . '_redemption_amount'} = 0;
                    $rowObj->{'month' . $i . '_rebate_amount'} = 0;
                }
                $table[$row->merchant_id] = $rowObj;
            }

            foreach ($this->table as $row) {
                $amount_key = "month{$row->month}_redemption_amount";
                $rebate_key = "month{$row->month}_rebate_amount";
                $table[$row->merchant_id]->{'month13_redemption_amount'} += $row->{self::FIELD_TOTAL_DOLLAR_VALUE_REDEEMED};
                $table[$row->merchant_id]->{'month13_rebate_amount'} += $row->{self::FIELD_TOTAL_DOLLAR_VALUE_REBATED};
                $table[$row->merchant_id]->{$amount_key} = $row->{self::FIELD_TOTAL_DOLLAR_VALUE_REDEEMED};
                $table[$row->merchant_id]->{$rebate_key} = $row->{self::FIELD_TOTAL_DOLLAR_VALUE_REBATED};
            }
        }

        foreach ($table as $key => $item) {
            $subRow = clone $item;
            unset($subRow->merchant_id);
            unset($subRow->merchant_name);

            $newItem = new stdClass();
            $newItem->merchant_id = $item->merchant_id;
            $newItem->merchant_name = $item->merchant_name;
            $newItem->subRows[] = $subRow;
            $table[$key] = $newItem;
        }
        $result= [];
        $result['data'] = $table;
        $result['total'] = $this->query instanceof Builder ? $this->query->count(DB::raw("DISTINCT `merchants`.`account_holder_id`")) : count($this->table);

        return $result;
    }

    protected function getReportForCSV(): array
    {
        $this->isExport = true;
        $this->params[self::SQL_LIMIT] = null;
        $this->params[self::SQL_OFFSET] = null;

        $table = $this->getTable();
        $newTable = [];
        foreach ($table['data'] as $key => $item) {
            foreach (array_shift($item->subRows) as $subKey => $subItem){
                $table['data'][$key]->{$subKey} = $subItem;
            }
            unset($item->subRows);
            $newTable[] = $table['data'][$key];
        }

        $data['data'] = $newTable;
        $data['total'] = $table['total'];
        $data['headers'] = $this->getCsvHeaders();
        return $data;
    }

    public function getCsvHeaders(): array
    {
        return [
            [
                'label' => 'Merchant',
                'key' => 'merchant_name'
            ],
            [
                'label' => 'Jan',
                'key' => 'month1_redemption_amount'
            ],
            [
                'label' => 'Feb',
                'key' => 'month2_redemption_amount'
            ],
            [
                'label' => 'Mar',
                'key' => 'month3_redemption_amount'
            ],
            [
                'label' => 'Apr',
                'key' => 'month4_redemption_amount'
            ],
            [
                'label' => 'May',
                'key' => 'month5_redemption_amount'
            ],
            [
                'label' => 'Jun',
                'key' => 'month6_redemption_amount'
            ],
            [
                'label' => 'Jul',
                'key' => 'month7_redemption_amount'
            ],
            [
                'label' => 'Aug',
                'key' => 'month8_redemption_amount'
            ],
            [
                'label' => 'Sep',
                'key' => 'month9_redemption_amount'
            ],
            [
                'label' => 'Oct',
                'key' => 'month10_redemption_amount'
            ],
            [
                'label' => 'Nov',
                'key' => 'month11_redemption_amount'
            ],
            [
                'label' => 'Dec',
                'key' => 'month12_redemption_amount'
            ],
            [
                'label' => 'YTD',
                'key' => 'month13_redemption_amount'
            ],
        ];
    }

}
