<?php

namespace App\Services\reports;

use App\Models\MediumInfo;
use App\Models\Merchant;
use App\Models\OptimalValue;

class ReportInventoryService extends ReportServiceAbstract
{
    protected function calc(): array
    {
        $table = [];

        // Parse and format the date
        $this->params[self::DATE_FROM] = $this->params[self::DATE_FROM] ?: date("m/d/Y");
        $date = \DateTime::createFromFormat('m/d/Y', $this->params[self::DATE_FROM]);
        $endDate = $date !== false ? $date->format('Y-m-d') : date('Y-m-d');

        // Retrieve merchants and SKU values
        $merchants = Merchant::tree()->depthFirst()->whereIn('id', $this->params[self::MERCHANTS])->get();
        $skuValues = MediumInfo::getSkuValues();

        foreach ($merchants as $merchant) {
            $merchantId = (int)$merchant->account_holder_id;
            $table[$merchantId] = (object)$merchant->toArray();

            // Initialize data for each merchant
            foreach ($skuValues as $skuValue) {
                $formattedSkuValue = number_format($skuValue, 2, '.', '');
                $table[$merchantId]->on_hand[$formattedSkuValue] = 0;
                $table[$merchantId]->optimal_values[$formattedSkuValue] = 0;
                $table[$merchantId]->percent_remaining[$formattedSkuValue] = 0;
            }

            // Process inventory and optimal values
            if (!$merchant->get_gift_codes_from_root) {
                $denominationList = MediumInfo::getRedeemableDenominationsByMerchant($merchantId, $endDate);
                foreach ($denominationList as $denomination) {
                    $skuValueFormatted = number_format($denomination->sku_value, 2, '.', '');
                    $table[$merchantId]->on_hand[$skuValueFormatted] += $denomination->count;
                }

                $optimalValues = OptimalValue::getByMerchantId($merchantId);
                foreach ($optimalValues as $optimalValue) {
                    $skuValueFormatted = number_format($optimalValue->denomination, 2, '.', '');
                    $table[$merchantId]->optimal_values[$skuValueFormatted] = $optimalValue->optimal_value;

                    if ($optimalValue->optimal_value > 0) {
                        $table[$merchantId]->percent_remaining[$skuValueFormatted] =
                            isset($table[$merchantId]->on_hand[$skuValueFormatted]) ?
                                $table[$merchantId]->on_hand[$skuValueFormatted] / $optimalValue->optimal_value : 0;
                    }
                }

                $costBasis = MediumInfo::getCostBasis($merchantId);
                $table[$merchantId]->cost_basis = $costBasis ?: 0;
            } else {
                $table[$merchantId]->cost_basis = '^';
                foreach ($skuValues as $skuValue) {
                    $formattedSkuValue = number_format($skuValue, 2, '.', '');
                    $table[$merchantId]->on_hand[$formattedSkuValue] = '^';
                    $table[$merchantId]->optimal_values[$formattedSkuValue] = '^';
                    $table[$merchantId]->percent_remaining[$formattedSkuValue] = '^';
                }
            }
        }

        if ($this->isExport) {
            $this->table['skuValues'] = $skuValues;
            $this->table['data'] = $this->prepareForExport($table);
            return $this->table;
        }

        $this->table['data']['report'] = $table;
        $this->table['data']['skuValues'] = $skuValues;
        $this->table['total'] = count($table);

        return $this->table;
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
