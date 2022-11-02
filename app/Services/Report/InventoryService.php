<?php

namespace App\Services\Report;

class InventoryService extends ReportService {

    public function __construct()
    {
        parent::__construct();
    }

    public function getReport() {

        $merchants = $this->model->Merchant->findByIds( $this->params->merchant_ids );

        // return $merchants;
 
        foreach ( $merchants as $row ) {
            $this->table[$row->{self::FIELD_ID}] = (object)$row->toArray();
        }

        $skuValues = $this->model->GiftCode->read_sku_values( $this->params->merchant_ids, $this->params->end_date )->pluck('sku_value')->toArray();
        // print_r($skuValues);

        if (is_array ( $this->table ) && count ( $this->table ) > 0) {
            foreach ( $this->table as $merchant_id => $merchant ) {
                $this->table [$merchant_id]->{self::FIELD_ON_HAND} = array ();
                $this->table [$merchant_id]->{self::FIELD_OPTIMAL_VALUES} = array ();
                $this->table [$merchant_id]->{self::FIELD_PERCENT_REMAINING} = array ();
                $this->table [$merchant_id]->{self::FIELD_COST_BASIS} = 0;
                if (is_array ( $skuValues ) && count ( $skuValues ) > 0) {
                    // print_r($skuValues);
                    foreach ( $skuValues as $sku_value ) {
                        $sku_value_amount = number_format ( $sku_value, 2, '.', '' ); // Get rid of commas and set to 2 decimal places
                        // print_r($sku_value_amount);
                        if (! $merchant->get_gift_codes_from_root) {
                            $this->table[$merchant_id]->{self::FIELD_ON_HAND}[$sku_value_amount] = 0;
                            $this->table[$merchant_id]->{self::FIELD_OPTIMAL_VALUES}[$sku_value_amount] = 0;
                            $this->table[$merchant_id]->{self::FIELD_PERCENT_REMAINING}[$sku_value_amount] = 0;
                        } else {
                            $this->table[$merchant_id]->{self::FIELD_ON_HAND}[$sku_value_amount] = '^';
                            $this->table[$merchant_id]->{self::FIELD_OPTIMAL_VALUES}[$sku_value_amount] = '^';
                            $this->table[$merchant_id]->{self::FIELD_PERCENT_REMAINING}[$sku_value_amount] = '^';
                        }
                    }
                }
                // print_r($this->table [$merchant_id]);
            }
			// For each merchant get the amount of inventory they have on hand and the optimal values set
			foreach ( $this->table as $merchant_id => $merchant ) {
                // print_r($this->table [$merchant_id]);
				if (! $merchant->get_gift_codes_from_root) {
					// Read the amount in inventory for each merchant
					$denomination_list = $this->model->GiftCode->read_list_redeemable_denominations_by_merchant ( ( int ) $merchant->id, $this->params->end_date );
					// Apply the denomination list to the result
					if (is_array ( $denomination_list ) && count ( $denomination_list ) > 0) {
						foreach ( $denomination_list as $denomination ) {
							$sku_value_amount = number_format ( $denomination->sku_value, 2, '.', '' ); // Get rid of commas and set to 2 decimal places
							if (! $merchant->get_gift_codes_from_root) {
								$this->table[$merchant_id]->{self::FIELD_ON_HAND}[$sku_value_amount] += $denomination->count;
							} else {
								$this->table [$merchant_id]->{self::FIELD_ON_HAND}[$sku_value_amount] = "^";
							}
						}
					}
                    // print_r($this->table[$merchant_id]);
				}
				// Read the optimal value settings for each merchant
				$optimal_values_list = $this->_ci->optimal_values_model->read_list_by_merchant_id ( ( int ) $merchant->id, 0, 999999 );
				// Apply the optimal values list to the result
				if (is_array ( $optimal_values_list ) && count ( $optimal_values_list ) > 0) {
					foreach ( $optimal_values_list as $optimal_value ) {
						$sku_value_amount = number_format ( $optimal_value->denomination, 2, '.', '' ); // Get rid of commas and set to 2 decimal places
						if (! $merchant->get_gift_codes_from_root) {
							$this->table [$merchant_id]->{self::FIELD_OPTIMAL_VALUES} [$sku_value_amount] = $optimal_value->optimal_amount;
							// Calculate the percent remaining
							if ($optimal_value->optimal_amount > 0) {
								if (isset ( $this->table [$merchant->account_holder_id]->{self::FIELD_ON_HAND} [$sku_value_amount] )) {
									$this->table [$merchant_id]->percent_remaining [$sku_value_amount] = $this->table [$merchant->account_holder_id]->{self::FIELD_ON_HAND} [$sku_value_amount] / $optimal_value->optimal_amount;
								} else {
									$this->table [$merchant_id]->percent_remaining [$sku_value_amount] = 0;
								}
							}
						} else {
							$this->table [$merchant_id]->{self::FIELD_OPTIMAL_VALUES} [$sku_value_amount] = "^";
							$this->table [$merchant_id]->percent_remaining [$sku_value_amount] = "^";
						}
					}
				}
				if (! $merchant->get_gift_codes_from_root) {
					$temp_params [self::MERCHANTS] = $merchant->account_holder_id;
					$temp_params [self::SQL_OFFSET] = '';
					$temp_params [self::SQL_LIMIT] = '';
					// Run the inventory cost basis sub report for this merchant
					$reportCostBasis = new Report_handler_inventory_cost_basis ( $temp_params );
					$this->table [$merchant_id]->{self::FIELD_COST_BASIS} = $reportCostBasis->getTable ();
				} else {
					$this->table [$merchant_id]->{self::FIELD_COST_BASIS} = "^";
				}
			}
        }

        // return $giftCodes;

        return $this->table;
    }
}