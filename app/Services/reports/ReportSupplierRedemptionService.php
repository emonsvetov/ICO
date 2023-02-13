<?php

namespace App\Services\reports;

use App\Models\MediumInfo;
use App\Models\Merchant;
use App\Models\OptimalValue;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Expr\Cast\Object_;
use stdClass;

class ReportSupplierRedemptionService extends ReportServiceAbstract
{
    const FIELD_TOTAL_DOLLAR_COST_BASIS = 'total_cost_basis';

    const FIELD_TOTAL_DOLLAR_PREMIUM = 'total_premium';

    const FIELD_TOTAL_DOLLAR_REDEMPTION_VALUE = 'total_redemption_value';

    const FIELD_AVG_DISCOUNT_PERCENT = 'avg_discount_percent';

    const FIELD_PERCENT_TOTAL_REDEMPTION_VALUE = 'percent_total_redemption_value';

    const FIELD_PERCENT_TOTAL_COST = 'percent_total_cost';

    const FIELD_REDEMPTIONS = 'redemptions';

    const FIELD_REDEMPTION_VALUE = 'redemption_value';

    const FIELD_SKU_VALUE = 'sku_value';

    private $total = [];


    protected function getBaseSql(): Builder
    {
        $report_key = $this->params[self::FIELD_REPORT_KEY] ?? self::FIELD_REDEMPTION_VALUE;

        $query = DB::table('merchants');
        $query->join('medium_info', 'medium_info.merchant_id', '=', 'merchants.id');
        $query->addSelect(
            DB::raw("
                COUNT(*) as count,
                SUM(cost_basis) as 'total_cost_basis',
                SUM(redemption_value - sku_value) as 'total_premium',
                sku_value,
                redemption_value,
                merchant_id as id
            "),
        );

        if ($report_key == self::FIELD_REDEMPTION_VALUE) {
            $query->addSelect(
                DB::raw("
                    SUM(redemption_value) as 'total_redemption_value'
            "),
            );
        } else {
            $query->addSelect(
                DB::raw("
                    SUM(sku_value) as 'total_redemption_value'
            "),
            );
        }

        return $query;
    }

    /**
     * @inheritDoc
     */
    protected function setWhereFilters(Builder $query): Builder
    {
        $query->whereNotNull('medium_info.redemption_date');
        $query->whereIn('merchants.id', $this->params[self::MERCHANTS]);
        if ($this->params [self::MERCHANTS_ACTIVE]) {
            $query->where('merchants.status', '=', 1);
        }
        $query->whereBetween('medium_info.redemption_date',
            [$this->params[self::DATE_FROM], $this->params[self::DATE_TO]]);
        return $query;
    }

    /**
     * @inheritDoc
     */
    protected function setGroupBy(Builder $query): Builder
    {
        $query->groupBy('sku_value', 'redemption_value', 'merchants.id');
        return $query;
    }

    public function getTable(): array
    {
        parent::getTable();

        $data = $this->table['data'];

        $table['merchants'] = [];
        $report_key = $this->params[self::FIELD_REPORT_KEY] ?? self::FIELD_REDEMPTION_VALUE;
        $merchants = Merchant::getFlatTree();

        $total_row = (object)[];
        $total_row->{self::FIELD_TOTAL_DOLLAR_COST_BASIS} = 0;
        $total_row->{self::FIELD_TOTAL_DOLLAR_REDEMPTION_VALUE} = 0;
        $total_row->{self::FIELD_AVG_DISCOUNT_PERCENT} = 0;
        $total_row->{self::FIELD_PERCENT_TOTAL_REDEMPTION_VALUE} = 0;
        $total_row->{self::FIELD_TOTAL_DOLLAR_PREMIUM} = 0;
        $total_row->{self::FIELD_PERCENT_TOTAL_COST} = 0;
        $total_row->{self::FIELD_REDEMPTIONS} = [];

        $neededIds = array_column($data, 'id');
        foreach ($merchants as $merchant) {
            if (in_array($merchant->id, $neededIds)) {
                $merchant = (object)['id' => $merchant->id, 'name' => $merchant->name];
                $merchant->{self::FIELD_TOTAL_DOLLAR_COST_BASIS} = 0;
                $merchant->{self::FIELD_TOTAL_DOLLAR_REDEMPTION_VALUE} = 0;
                $merchant->{self::FIELD_AVG_DISCOUNT_PERCENT} = 0;
                $merchant->{self::FIELD_TOTAL_DOLLAR_PREMIUM} = 0;
                $merchant->{self::FIELD_PERCENT_TOTAL_REDEMPTION_VALUE} = 0;
                $merchant->{self::FIELD_PERCENT_TOTAL_COST} = 0;
                $merchant->{self::FIELD_REDEMPTIONS} = [];
                $table['merchants'][$merchant->id] = $merchant;
            }
        }

        if (is_array($data) && count($data) > 0) {
            // create the starting table and prime with 0's
            foreach ($data as $row) {
                if ( ! isset ($table['merchants'][$row->id])) {
                    continue;
                }

                $table['merchants'][$row->id]->{self::FIELD_TOTAL_DOLLAR_COST_BASIS} += $row->{self::FIELD_TOTAL_DOLLAR_COST_BASIS};
                $table['merchants'][$row->id]->{self::FIELD_TOTAL_DOLLAR_REDEMPTION_VALUE} += $row->{self::FIELD_TOTAL_DOLLAR_REDEMPTION_VALUE};
                $table['merchants'][$row->id]->{self::FIELD_TOTAL_DOLLAR_PREMIUM} += $row->{self::FIELD_TOTAL_DOLLAR_PREMIUM};
                if ( ! isset ($table['merchants'][$row->id]->{self::FIELD_REDEMPTIONS}[( float )$row->$report_key])) {
                    $table['merchants'][$row->id]->{self::FIELD_REDEMPTIONS}[( float )$row->$report_key] = 0;
                }
                $table['merchants'][$row->id]->{self::FIELD_REDEMPTIONS}[( float )$row->$report_key] += $row->count;


                // Add to the total row
                $total_row->{self::FIELD_TOTAL_DOLLAR_COST_BASIS} += $row->{self::FIELD_TOTAL_DOLLAR_COST_BASIS};
                $total_row->{self::FIELD_TOTAL_DOLLAR_REDEMPTION_VALUE} += $row->{self::FIELD_TOTAL_DOLLAR_REDEMPTION_VALUE};
                $total_row->{self::FIELD_TOTAL_DOLLAR_PREMIUM} += $row->{self::FIELD_TOTAL_DOLLAR_PREMIUM};
                if ( ! isset ($total_row->{self::FIELD_REDEMPTIONS}[( float )$row->$report_key])) {
                    $total_row->{self::FIELD_REDEMPTIONS}[( float )$row->$report_key] = 0;
                }
                $total_row->{self::FIELD_REDEMPTIONS}[( float )$row->$report_key] += $row->count;
            }
        }

        foreach ($table['merchants'] as $id => $merchant) {
            $merchants_redemption_totals = [];
            foreach ($merchant->{self::FIELD_REDEMPTIONS} as $merchant_redemption_value => $merchant_redemption_count) {
                $merchants_redemption_totals[] = [
                    'value' => $merchant_redemption_value,
                    'count' => $merchant_redemption_count
                ];
            }
            $table['merchants'][$id]->{self::FIELD_REDEMPTIONS} = $merchants_redemption_totals;
        }

        $redemption_totals = [];
        foreach ($total_row->{self::FIELD_REDEMPTIONS} as $key_value => $redemption_count) {
            $redemption_totals[] = [
                'value' => $key_value,
                'count' => $redemption_count
            ];
        }
        $total_row->{self::FIELD_REDEMPTIONS} = $redemption_totals;

        // Calculate the total cost and redemption percents
        if (count($table['merchants']) > 0) {
            foreach ($table['merchants'] as $merchant_row) {
                if ($total_row->{self::FIELD_TOTAL_DOLLAR_REDEMPTION_VALUE} !== 0) {
                    $merchant_row->{self::FIELD_PERCENT_TOTAL_REDEMPTION_VALUE} = $merchant_row->{self::FIELD_TOTAL_DOLLAR_REDEMPTION_VALUE} / $total_row->{self::FIELD_TOTAL_DOLLAR_REDEMPTION_VALUE} * 100;
                } else {
                    $merchant_row->{self::FIELD_PERCENT_TOTAL_REDEMPTION_VALUE} = 0;
                }
                if ($total_row->{self::FIELD_TOTAL_DOLLAR_COST_BASIS} !== 0) {
                    $merchant_row->{self::FIELD_PERCENT_TOTAL_COST} = $merchant_row->{self::FIELD_TOTAL_DOLLAR_COST_BASIS} / $total_row->{self::FIELD_TOTAL_DOLLAR_COST_BASIS} * 100;
                } else {
                    $merchant_row->{self::FIELD_PERCENT_TOTAL_COST} = 0;
                }
                $total_row->{self::FIELD_PERCENT_TOTAL_COST} += $merchant_row->{self::FIELD_PERCENT_TOTAL_COST};
                $total_row->{self::FIELD_PERCENT_TOTAL_REDEMPTION_VALUE} += $merchant_row->{self::FIELD_PERCENT_TOTAL_REDEMPTION_VALUE};
                $params = $this->params;
                $params[self::MERCHANTS] = [$merchant_row->id];
                $average_discount_report = new ReportSupplierRedemptionTotalAverageService($params);
                $average_discount_report = $average_discount_report->getTable()['data'];
                $average_discount_report = $average_discount_report ? array_shift($average_discount_report)->avg_discount_percent : 0;
                $merchant_row->{self::FIELD_AVG_DISCOUNT_PERCENT} = $average_discount_report;
                $total_row->{self::FIELD_AVG_DISCOUNT_PERCENT} += $merchant_row->{self::FIELD_AVG_DISCOUNT_PERCENT};
            }
        }

        $this->total = $total_row;
        return ['data' => $table['merchants'], 'count' => count($table['merchants'])];
    }

    protected function getReportForCSV(): array
    {
        $this->isExport = true;
        $this->params[self::SQL_LIMIT] = null;
        $this->params[self::SQL_OFFSET] = null;
        $data = $this->getTable();
        sort($data['data']);
        $data['data'][] = [
            'name' => 'Total',
            'total_redemption_value' => $this->total->total_redemption_value,
            'total_premium' => $this->total->total_premium,
            'percent_total_redemption_value' => $this->total->percent_total_redemption_value,
            'total_cost_basis' => $this->total->total_cost_basis,
            'percent_total_cost' => $this->total->percent_total_cost,
            'avg_discount_percent' => $this->total->avg_discount_percent,
        ];
        $data['headers'] = $this->getCsvHeaders();
        return $data;
    }

    public function getCsvHeaders(): array
    {
        return [
            [
                'label' => 'Merchant',
                'key' => 'name'
            ],
            [
                'label' => 'Total Redemption Value',
                'key' => 'total_redemption_value'
            ],
            [
                'label' => 'Total Premium',
                'key' => 'total_premium'
            ],
            [
                'label' => 'Percent Total Redemption Value',
                'key' => 'percent_total_redemption_value'
            ],
            [
                'label' => 'Total Cost',
                'key' => 'total_cost_basis'
            ],
            [
                'label' => 'Percent Total Cost',
                'key' => 'percent_total_cost'
            ],
            [
                'label' => 'Average Discount',
                'key' => 'avg_discount_percent'
            ],
        ];
    }

}
