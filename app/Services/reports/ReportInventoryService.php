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
        $this->params[self::DATE_FROM] = $this->params[self::DATE_FROM] ?: date("Y-m-d H:i:s");
        $date = \DateTime::createFromFormat('Y-m-d H:i:s', $this->params[self::DATE_FROM]);
        $endDate = $date->format('Y-m-d');

        $this->table = [];
        $merchants = Merchant::tree()->depthFirst()->whereIn('id', $this->params[self::MERCHANTS])->get();
        $skuValues = MediumInfo::getSkuValues();

        foreach ($merchants as $key => $merchant) {
            $merchantId = (int)$merchant->account_holder_id;
            $table[$merchantId] = (object)$merchant->toArray();
            foreach ($skuValues as $sku_value) {
                $sku_value = number_format($sku_value, 2, '.', '');
                if ( ! $merchant['get_gift_codes_from_root']) {
                    $table[$merchantId]->on_hand[$sku_value] = 0;
                    $table[$merchantId]->optimal_values[$sku_value] = 0;
                    $table[$merchantId]->percent_remaining[$sku_value] = 0;
                } else {
                    $table[$merchantId]->on_hand[$sku_value] = '^';
                    $table[$merchantId]->optimal_values[$sku_value] = '^';
                    $table[$merchantId]->percent_remaining[$sku_value] = '^';
                }
            }
        }


        /** For each merchant get the amount of inventory they have on hand and the optimal values set */
        foreach ($table as $merchantId => $merchant) {
            if ( ! $merchant->get_gift_codes_from_root) {
                /** Read the amount in inventory for each merchant */
                $denominationList = MediumInfo::getRedeemableDenominationsByMerchant($merchantId, $endDate);

                foreach ($denominationList as $denomination) {
                    $skuValueAmount = number_format($denomination->sku_value, 2, '.', '');
                    if ( ! $merchant->get_gift_codes_from_root) {
                        $table[$merchantId]->on_hand[$skuValueAmount] += $denomination->count;
                    } else {
                        $table[$merchantId]->on_hand[$skuValueAmount] = "^";
                    }
                }
            }

            $optimalValues = OptimalValue::getByMerchantId($merchantId);
            foreach ($optimalValues as $optimalValue) {
                $skuValueAmount = number_format($optimalValue->denomination, 2, '.', '');


                if ( ! $merchant->get_gift_codes_from_root) {
                    $table[$merchantId]->optimal_values[$skuValueAmount] = $optimalValue->optimal_value;
                    if ($optimalValue->optimal_value > 0) {
                        if (isset($table[$merchantId]->on_hand[$skuValueAmount])) {
                            $table[$merchantId]->percent_remaining[$skuValueAmount] =
                                $table[$merchantId]->on_hand[$skuValueAmount] / $optimalValue->optimal_value;
                        } else {
                            $table[$merchantId]->percent_remaining[$skuValueAmount] = 0;
                        }
                    }
                } else {
                    $table[$merchantId]->optimal_values[$skuValueAmount] = "^";
                    $table[$merchantId]->percent_remaining[$skuValueAmount] = "^";
                }
            }

            if ( ! $merchant->get_gift_codes_from_root) {
                $costBasis = MediumInfo::getCostBasis($merchantId);
                $table[$merchantId]->cost_basis = $costBasis;
            } else {
                $table[$merchantId]->cost_basis = "^";
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
