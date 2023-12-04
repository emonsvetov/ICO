<?php

namespace App\Services\reports;

use App\Models\MediumInfo;
use App\Models\Merchant;
use App\Models\OptimalValue;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Expr\Cast\Object_;
use stdClass;

class ReportInventoryOrderService extends ReportServiceAbstract
{
    protected function calc(): array
    {
        $table = [];
        $this->table = [];

        $query = Merchant::select(
            "merchants.name as merchant_name",
            "merchants.account_holder_id",
            "merchant_optimal_values.denomination",
            "merchant_optimal_values.optimal_value",
        )
            ->join('merchant_optimal_values', 'merchant_optimal_values.merchant_id', '=',
                'merchants.id');
        $result = $query->get();

        foreach ($result as $row) {
            if ( ! isset ($table[$row->account_holder_id])) {
                $table[$row->account_holder_id] = new stdClass();
                $table[$row->account_holder_id]->merchant_name = $row->merchant_name;
                $table[$row->account_holder_id]->optimal_values = [];
            }
            $reportRow = new stdClass();
            $reportRow->denomination = number_format($row->denomination, 2);
            $reportRow->optimal_value = $row->optimal_value;
            $reportRow->count = 0;
            $table[$row->account_holder_id]->optimal_values[$reportRow->denomination] = $reportRow;
        }
        foreach ($table as $merchantId => $values) {
            $inventory = MediumInfo::getRedeemableDenominationsByMerchant((int)$merchantId);
            foreach ($inventory as $inventoryRow) {
                $skuValue = number_format($inventoryRow->sku_value, 2);
                if ( ! isset ($values->optimal_values[$skuValue])) {
                    $reportRow = new stdClass ();
                    $reportRow->denomination = number_format($skuValue, 2);
                    $reportRow->optimal_value = 0;
                    $reportRow->count = 0;
                    $values->optimal_values[$skuValue] = $reportRow;
                }
                if (isset($values->optimal_values[$skuValue]->count)) {
                    // Make sure to add since we might get results with the same sku but different redemption values
                    $values->optimal_values[$skuValue]->count += $inventoryRow->count;
                } else {
                    $values->optimal_values[$skuValue]->count = $inventoryRow->count;
                }
            }
        }

        // needed flat array to csv export
        if ($this->isExport) {
            $this->table['data'] = $this->prepareForExport($table);
            return $this->table;
        }

        // prepare data for react-table with rowSpan
        $arr = [];
        foreach ($table as $item) {

            if (count($item->optimal_values) >= 1) {
                $row = [];
                $row['merchant_name'] = $item->merchant_name;
                $row['denomination'] = 'skip_td';
                $row['count'] = 'skip_td';
                $row['optimal_value'] = 'skip_td';
                $subRows = [];
                foreach ($item->optimal_values as $subKey => $subItem) {
                    $subRows[] =
                        [
                            'id' => $item->merchant_name . $subKey,
                            'merchant_name' => 'skip_td',
                            'denomination' => $subItem->denomination,
                            'count' => $subItem->count,
                            'optimal_value' => $subItem->optimal_value,
                        ];
                }
                $row['subRows'] = $subRows;
                $arr[] = $row;
            }
        }

        $this->table['data'] = $arr;
        $this->table['total'] = count($arr);
        return $this->table;
    }

    public function getCsvHeaders(): array
    {
        return [
            [
                'label' => 'Merchant Name',
                'key' => 'merchant_name'
            ],
            [
                'label' => 'Denomination',
                'key' => 'denomination'
            ],
            [
                'label' => '#in Inventory',
                'key' => 'count'
            ],
            [
                'label' => '2-Week Target',
                'key' => 'optimal_value'
            ],
        ];
    }

    private function prepareForExport($table): array
    {
        $arr = [];
        foreach ($table as $item) {
            if (count($item->optimal_values) >= 1) {
                foreach ($item->optimal_values as $subKey => $subItem) {
                    $row = [];
                    $row['merchant_name'] = $item->merchant_name;
                    $row['denomination'] = $subItem->denomination;
                    $row['count'] = $subItem->count;
                    $row['optimal_value'] = $subItem->optimal_value;
                    $arr[] = $row;
                }
            }
        }
        return $arr;
    }

}
