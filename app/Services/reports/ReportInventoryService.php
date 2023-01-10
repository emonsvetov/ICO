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

        $this->table['data']['report'] = $table;
        $this->table['data']['skuValues'] = $skuValues;
        $this->table['total'] = count($table);

        return $this->table;
    }

    protected function getReportForCSV(): array
    {
        $this->params[self::SQL_LIMIT] = null;
        $this->params[self::SQL_OFFSET] = null;
        $data = $this->getTable();
        $tmpData = [];
        $tmpRow = [];
//        print_r($data);
//        die;
        foreach ($data['data']['report'] as $merchant_id => $inventory_data) {
            $tmpRow = [];
            $tmpRow[] = $inventory_data->name;
            foreach ($data['data']['skuValues'] as $sku_value) {
                $value = '-';
                $sku_value_amount = number_format($sku_value, 2, '.',
                    ''); // Get rid of commas and set to 2 decimal places
                if (isset ($inventory_data->on_hand [$sku_value_amount]) && $inventory_data->on_hand [$sku_value_amount] != 'na') {
                    $value = $inventory_data->on_hand[$sku_value_amount];
                }
                $tmpRow[] = $value;
            }
            foreach ($data['data']['skuValues'] as $sku_value) {
                $value = '-';
                $sku_value_amount = number_format($sku_value, 2, '.',
                    ''); // Get rid of commas and set to 2 decimal places
                if (isset ($inventory_data->optimal_values [$sku_value_amount]) && $inventory_data->optimal_values [$sku_value_amount] != 'na') {
                    $value = $inventory_data->optimal_values[$sku_value_amount];
                }
                $tmpRow[] = $value;
            }
            foreach ($data['data']['skuValues'] as $sku_value) {
                $value = '-';
                $sku_value_amount = number_format($sku_value, 2, '.', '');
                if (isset ($inventory_data->percent_remaining [$sku_value_amount])) {
                    if (( string )$inventory_data->percent_remaining [$sku_value_amount] == "^") {
                        $value = '^';
                    } else {
                        if ($inventory_data->percent_remaining [$sku_value_amount] != 'na') {
                            $value = number_format($inventory_data->percent_remaining [$sku_value_amount] * 100, 0, '.',
                                    '') . "%";
                        }
                    }
                }
                $tmpRow[] = $value;
            }
            $value = '-';
            if ($inventory_data->cost_basis == "^") {
                $value = '^';
            } else if ($inventory_data->cost_basis != '0') {
                $value = '$' . number_format ( $inventory_data->cost_basis, 2, '.', '' );
            }
            $tmpRow[] = $value;
            $tmpData[] = $tmpRow;
        }

        $result = [
            'data' => $tmpData,
            'headers' => [] // $this->getCsvHeaders(),
            ];
        return $result;
    }

    public function getCsvHeaders(): array
    {
        return [
            [
                'label' => 'Program Name',
                'key' => 'program_name'
            ],
        ];
    }

}
