<?php

namespace App\Services\reports;

use App\Models\MediumInfo;
use App\Models\Merchant;
use App\Models\OptimalValue;
use Carbon\Carbon;

class ReportInventoryService extends ReportServiceAbstract
{
    protected function calc(): array
    {
        $table = [];

        // Parse and format the date
        $date = Carbon::parse($this->params[self::DATE_FROM] ?: now());
        $endDate = $date->format('Y-m-d');
        $this->params[self::DATE_FROM] = $endDate;

        // Retrieve merchants and SKU values
        $merchants = Merchant::tree()->depthFirst()->whereIn('id', $this->params[self::MERCHANTS])->get();
        $skuValues = MediumInfo::getSkuValues();

        foreach ($merchants as $merchant) {
            $merchantId = (int) $merchant->account_holder_id;
            $table[$merchantId] = (object) $merchant->toArray();

            $table[$merchantId]->on_hand = [];
            $table[$merchantId]->optimal_values = [];
            $table[$merchantId]->percent_remaining = [];
            $table[$merchantId]->cost_basis = 0;

            // Initialize data for each merchant
            foreach ($skuValues as $skuValue) {

                $formattedSkuValue = number_format($skuValue, 2, '.', '');

                if (!$merchant->get_gift_codes_from_root) {
                    $table[$merchantId]->on_hand[$formattedSkuValue] = 0;
                    $table[$merchantId]->optimal_values[$formattedSkuValue] = 0;
                    $table[$merchantId]->percent_remaining[$formattedSkuValue] = 0;
                }
                else {
                    $table[$merchantId]->on_hand[$formattedSkuValue] = '^';
                    $table[$merchantId]->optimal_values[$formattedSkuValue] = '^';
                    $table[$merchantId]->percent_remaining[$formattedSkuValue] = '^';
                }
            }
        }

        foreach ($merchants as $merchant) {
            $merchantId = (int) $merchant->account_holder_id;
            if (!$merchant->get_gift_codes_from_root) {
                $denominationList = MediumInfo::getRedeemableDenominationsByMerchant($merchantId, $endDate, ['inventoryType' => $this->params[self::INVENTORY_TYPE]]);
                foreach ($denominationList as $denomination) {
                    $skuValueFormatted = number_format($denomination->sku_value, 2, '.', '');
                    $table[$merchantId]->on_hand[$skuValueFormatted] += $denomination->count;
                }
            }

            $optimalValues = OptimalValue::getByMerchantId($merchant->id);
            foreach ($optimalValues as $optimalValue) {
                $skuValueFormatted = number_format($optimalValue->denomination, 2, '.', '');
                if (!$merchant->get_gift_codes_from_root) {
                    $table[$merchantId]->optimal_values[$skuValueFormatted] = $optimalValue->optimal_value;

                    if ($optimalValue->optimal_value > 0) {
                        if (isset($table[$merchantId]->on_hand[$skuValueFormatted])) {
                            $table[$merchantId]->percent_remaining[$skuValueFormatted] = $table[$merchantId]->on_hand[$skuValueFormatted] / $optimalValue->optimal_value;
                        }
                        else {
                            $table[$merchantId]->percent_remaining[$skuValueFormatted] = 0;
                        }
                    }
                }
                else {
                    $table[$merchantId]->optimal_values[$skuValueFormatted] = '^';
                    $table[$merchantId]->percent_remaining[$skuValueFormatted] = '^';
                }
            }

            if (!$merchant->get_gift_codes_from_root) {
                $table[$merchantId]->cost_basis = MediumInfo::getCostBasis($merchant->id, ['inventoryType' => $this->params[self::INVENTORY_TYPE], 'endDate' => $endDate]);

            }
            else {
                $table[$merchantId]->cost_basis = '^';
            }

        }

        $this->clearZeroColumns($table, $skuValues);

        $this->table['data']['report'] = $table;
        $this->table['data']['skuValues'] = $skuValues;
        $this->table['total'] = count($table);

        return $this->table;
    }

    /**
     * Clear zero columns in report table.
     *
     * @param $table
     * @param $skuValues
     */
    public function clearZeroColumns(&$table, $skuValues)
    {
        foreach ($skuValues as $skuValue) {
            $formattedSkuValue = number_format($skuValue, 2, '.', '');

            $columnCanClear = true;
            foreach (['on_hand',
                      'optimal_values',
                      'percent_remaining',
            ] as $item) {
                foreach ($table as $merchant) {
                    $merchantItem = $merchant->{$item};
                    if (
                        isset($merchantItem[$formattedSkuValue]) &&
                        (int) $merchantItem[$formattedSkuValue] > 0
                    ) {
                        $columnCanClear = false;
                    }
                }
            }

            if ($columnCanClear) {
                foreach (['on_hand',
                          'optimal_values',
                          'percent_remaining',
                         ] as $item) {
                    foreach ($table as &$merchant) {
                        unset($merchant->{$item}[$formattedSkuValue]);
                    }
                }
            }
        }
    }


    public function getCsvHeaders(): array
    {
        $arr = [];
        $arr[] = [
            'label' => 'Merchant Name',
            'key' => 'name'
        ];
        if ($this->table['skuValues']) {
            $z = 0;
            for ($i = 0; $i < 3; $i++) {
                foreach ($this->table['skuValues'] as $item) {
                    $arr[] = [
                        'label' => number_format((float)$item, 2, '.', ''),
                        'key' => 'key' . $z . number_format((float)$item, 2, '.', '')
                    ];
                    $z++;
                }
            }
        }
        $arr[] = [
            'label' => 'Cost Basis',
            'key' => 'cost_basis'
        ];

        return $arr;
    }

    private function prepareForExport($table): array
    {
        $arr = [];
        foreach ($table as $item) {
            $z = 0;
            $row = [];
            $row['name'] = $item->name;
            foreach ($item->on_hand as $subKey => $subItem) {
                $row['key' . $z . $subKey] = $subItem;
                $z++;
            }
            foreach ($item->optimal_values as $subKey => $subItem) {
                $row['key' . $z . $subKey] = $subItem;
                $z++;
            }
            foreach ($item->percent_remaining as $subKey => $subItem) {
                $row['key' . $z . $subKey] = $subItem;
                $z++;
            }
            $row['cost_basis'] = $item->cost_basis;
            $arr[] = $row;
        }
        return $arr;
    }
}
