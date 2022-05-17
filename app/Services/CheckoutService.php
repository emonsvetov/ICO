<?php
namespace App\Services;

use Illuminate\Support\Facades\Log;
use App\Models\Traits\IdExtractor;
use App\Models\JournalEventType;
use App\Models\ExternalCallback;
use App\Models\JournalEvent;
use App\Models\FinanceType;
use App\Models\MediumType;
use App\Models\Currency;
use App\Models\Giftcode;
use App\Models\Merchant;
use App\Models\Account;
use App\Models\Owner;
use DB;

class CheckoutService 
{
    use IdExtractor;

    public function processOrder( $cart, $program )   {

		$Logger = Log::channel('redemption');

		$response = [];
        $gift_codes = $cart['items'];
		$order_address = [];

		if( !$gift_codes ) return ['errors' => "No cart items in CheckoutService:processOrder"];

		$user = auth()->user();
		// $user->account_holder_id = $user->account_holder_id;

        $merchantsCostToProgram = array ();
		// $air_premium_cost_to_program = $program->air_premium_cost_to_program;
		$air_premium_cost_to_program = true;
		if ( $air_premium_cost_to_program ) {
			$merchants = Merchant::readListByProgram ( $program );
			foreach ( $merchants as $merchant ) {
				if ($merchant->pivot->cost_to_program) {
					$merchantsCostToProgram [] = $merchant->id;
				}
			}
		}

        $merchants_info = [];
        $owner_id = 1;

		// The total amount of points the user is attempting to redeem
		$redemption_value_total = 0;
		$all_external_callbacks = [];
		$gift_code_provider_id = [];
		$all_merchants = [];
		// Verify that the merchant and gift code denominations are valid
		// Also, total up the transaction
		// pr($gift_codes);
		$merchant_ids = [];

		foreach($gift_codes as $gift_code) {
			$merchant_ids[] = $gift_code['merchant_id'];
		}

        $merchants = Merchant::whereIn( 'id', $merchant_ids )->get();

		// $Logger->info("********** New Redemption from Program: AC:{$program->account_holder_id}/ID:{$program->id}/ ***********");
		// $Logger->info('Date: ' . date('Y-m-d H:i:s'));
		// $Logger->info("account_holder_id: $user->account_holder_id");
		// $Logger->info("program_id: $program->id");
		// $Logger->info("gift_codes_data: " . json_encode($gift_codes));
		// $Logger->info("order_address: " . json_encode($order_address));

		// pr($merchantsCostToProgram);

        foreach ( $gift_codes as &$gift_code_array ) {
			$gift_code = (object) $gift_code_array;
            $redemptionValue = 0;
			$redemptionFee = 0;
            $all_merchants[$gift_code->merchant_id] = $merchant = get_merchant_by_id($merchants, $gift_code->merchant_id);

            if (in_array ( $merchant->id, $merchantsCostToProgram )) {
				$gift_code_values_response = Giftcode::getRedeemableListByMerchant ( $merchant );
				//pr($gift_code_values_response);
				foreach ( $gift_code_values_response as $giftCode ) {
					$skuValue = ( int ) $giftCode->sku_value;
					$cartSkuValue = ( int ) $gift_code->sku_value;
					if ($skuValue == $cartSkuValue) {
						// Set the redemption value back to what it is supposed to be
						$gift_code->redemption_value = $giftCode->redemption_value;
						// Save redemtion value in variable for more readability when used
						$redemptionValue = $giftCode->redemption_value;
						$redemptionFee = $giftCode->redemption_fee;
						break;
					}
				}
				$redemption_value_total += $gift_code->sku_value * $gift_code->qty;
			} else {
				$redemption_value_total += $gift_code->redemption_value * $gift_code->qty;
				//pr($redemption_value_total);
			}

            // pr($redemption_value_total);     

            $merchants_info[$merchant->id] = $merchant;
			if ($merchant->get_gift_codes_from_root) {
				// $gift_code->gift_code_provider_id = Merchant::get_top_level_merchant_id ( $gift_code->merchant_id );
                // TODO
			} else {
				$gift_code->gift_code_provider_id = $gift_code->merchant_id;
			}

			$gift_code_provider_ids[$gift_code->merchant_id] = $gift_code->gift_code_provider_id;
			//pr($gift_code->gift_code_provider_id);exit;
			// If the option for "website is redemption url" store this merchant's website on the shopping cart item for later use
			if ($merchant->website_is_redemption_url) {
				// update the gift code record
				$gift_code->redemption_url = $merchant->website;
			}
			// run this check against the gift_code_provider_id
			$external_callbacks = ExternalCallback::read_list_by_type ( ( int ) $gift_code->gift_code_provider_id, 'B2B Gift Code' );
			// pr($external_callbacks->toArray());
			// pr(count ( $external_callbacks ));
			// // pr(DB::getQueryLog());
			// exit;
			if ( count ( $external_callbacks ) > 0 ) {
				//$debug['$external_callbacks'] = $external_callbacks;
				// Do not check this merchant's inventory level they use a callback to get giftcodes on the fly
				//Lets save for later use, 
				$all_external_callbacks[$gift_code->gift_code_provider_id] = $external_callbacks;
			} else {
				//$debug['no_external_callback'] = $external_callbacks;
				// Verify that there is enough inventory for this redemption value to complete this portion of the transaction
				// Billy added for cost to propgram
				// pr($merchant->account_holder_id);
				// pr($merchantsCostToProgram);
				// pr($gift_code->gift_code_provider_id);
				// pr( in_array ( $merchant->account_holder_id, $merchantsCostToProgram ) );
				if (in_array ( $merchant->id, $merchantsCostToProgram )) {
					$denomination_list = GiftCode::getRedeemableListByMerchantAndRedemptionValue ( $gift_code->gift_code_provider_id, $redemptionValue );
					if (! isset ( $denomination_list ) || count ( $denomination_list ) < 1) {
						// throw new RuntimeException ( 'Out of inventory' );
						$response['errors'][] = 'Out of inventory';
					}
					// Flag to indicate whether or not the redemption value was found in the denomination list
					$found_denomination = false;
					foreach ( $denomination_list as $denomination_info ) {
						if ($denomination_info->redemption_value == $redemptionValue && $denomination_info->sku_value == $gift_code->sku_value) {
							$found_denomination = true;
							if ($denomination_info->count < $gift_code->qty) {
								// throw new RuntimeException ( 'Insufficient inventory to complete the transaction' );
								$response['errors'][] = 'Insufficient inventory to complete the transaction';
							}
							break;
						}
					}
					if (! $found_denomination) {
						// throw new RuntimeException ( 'Out of inventory' );
						$response['errors'][] = 'Out of inventory';
					}
				} else {
					$denomination_list = GiftCode::getRedeemableListByMerchantAndRedemptionValue ( $gift_code->gift_code_provider_id, $gift_code->redemption_value );
					// pr($denomination_list);
					// pr(DB::getQueryLog());
					// exit;
					if (! isset ( $denomination_list ) || count ( $denomination_list ) < 1) {
						// throw new RuntimeException ( 'Out of inventory' );
						$response['errors'][] = 'Out of inventory';
					}
					// Flag to indicate whether or not the redemption value was found in the denomination list
					$found_denomination = false;
					foreach ( $denomination_list as $denomination_info ) {
						if ($denomination_info->redemption_value == $gift_code->redemption_value && $denomination_info->sku_value == $gift_code->sku_value) {
							$found_denomination = true;
							if ($denomination_info->count < $gift_code->qty) {
								// throw new RuntimeException ( 'Insufficient inventory to complete the transaction' );
								$response['errors'][] = 'Insufficient inventory to complete the transaction';
							}
							break;
						}
					}
					if (! $found_denomination) {
						// throw new RuntimeException ( 'Out of inventory' );
						$response['errors'][] = 'Out of inventory';
					}
				}
			}
        }

		$current_balance = $user->readAvailableBalance( $program, $user);

		if ($current_balance < $redemption_value_total) {
			$response['errors'][] = 'Current ending balance of the user is insufficient to redeem for the gift code';
		}

		$order_id = 0;
		if ( $order_address ) {
			// NOT TESTED YET, FOR NOW ASSUMING THAT IT IS NOT A PhysicalOrder
			$order_id = PhysicalOrder::create ( $user->account_holder_id, $program_id, $order_address );
		}

		$currency_type = Currency::getIdByType(config('global.default_currency'), true);

		$reserved_codes = array ();
		$redeem_merchant_info = array ();
		foreach ( $gift_codes as $gift_code2_array ) {
			$gift_code2 = (object) $gift_code2_array;

			$gift_code2->gift_code_provider_id = $gift_code_provider_ids[$gift_code2->merchant_id];

			for($i = 0; $i < $gift_code2->qty; ++ $i) {
				// NOT IMPLEMENTED YET
				// Need some work on Giftcode::_run_gift_code_callback
				// Check to see if the merchant uses a b2b redemption callback, if it does make the callback now and acquire the giftcode
				//$external_callbacks = $this->external_callbacks_model->read_list_by_type ( ( int ) $gift_code2->gift_code_provider_id, 'B2B Gift Code' );
				$external_callbacks = isset($all_external_callbacks[$gift_code2->gift_code_provider_id]) ? $all_external_callbacks[$gift_code2->gift_code_provider_id] : null ;
				//TODO TODO TODO TODO TODO TODO TODO TODO TODO TODO 
				// This section is TODO
				if ( $external_callbacks && count ( $external_callbacks ) > 0) {
					$debug['$external_callbacks'] = $external_callbacks;
					$data = array ();
					$data ['amount'] = ( float ) $gift_code2->redemption_value;
					$cb_response = Giftcode::_run_gift_code_callback ( $external_callbacks [0], $program_id, $user->account_holder_id, ( int ) $gift_code2->gift_code_provider_id, $data );
					if ($cb_response->response_code != '200') {
						$response['errors'][] = 'Error encountered when calling B2B Gift Code callback. ' . $cb_response->response_data;
					}
					$code = $cb_response->data;
					// Add the giftcode to the merchant's inventory
					$gift_code_id = ( int ) $this->create ( ( int ) $user->account_holder_id, ( int ) $gift_code2->gift_code_provider_id, $code );
					// Read the rest of the information about the code that was reserved
					$reserved_code = self::_read_by_merchant_and_medium_info_id ( ( int ) $gift_code2->gift_code_provider_id, $gift_code_id );
				} else {
					$debug['no_external_callbacks'] = $external_callbacks;
					// merchant hasn't external callback so store all values
					// store all values to redeem $redeem_merchant_info[merchant_id][code_value]
					$redeem_merchant_info [$gift_code2->merchant_id] [number_format ( $gift_code2->sku_value, 2 )] = array ();

					//pr($redeem_merchant_info);
					// BCM:HERE
					// Run this against the gift_code_provider_id
					//$debug['$user->account_holder_id'] = $user->account_holder_id;
					//$debug['$program_id'] = $program_id;
					//$debug['$gift_code_item2'] = $gift_code2;
					//$debug['$merchants_info'] = $merchants_info;

					$reserved_code = Giftcode::holdGiftcode ([
						'user_account_holder_id' => $user->account_holder_id,
						// 'program' => $program,
						// 'merchant_account_holder_id' => $gift_code2->gift_code_provider_id, 
						'merchant_account_holder_id' => $gift_code2->merchant_account_holder_id, 
						'redemption_value' => $gift_code2->redemption_value, 
						'sku_value' => $gift_code2->sku_value,
						'merchants' => $merchants->toArray()
					]);

					pr($reserved_code);
					exit;
					$debug['reserved_code'] = $reserved_code;

				}
				if (! isset ( $reserved_code )) {
					throw new RuntimeException ( "Unable to reserve GiftCode. {$gift_code->merchant_id}", 500 );
				}
				$reserved_code->gift_code_provider_id = ( int ) $gift_code2->gift_code_provider_id;
				if (! isset ( $reserved_code->merchant )) {
					$reserved_code->merchant = new stdClass ();
				}
				$reserved_code->merchant->account_holder_id = $gift_code2->merchant_id;
				// If the shopping cart item has a redemption url set, it means that the merchant that this code will be redeemed from
				// has the "website is redemption url" option turned on.
				if (isset ( $gift_code2->redemption_url ) && $gift_code2->redemption_url != '') {
					$reserved_code->redemption_url = $gift_code2->redemption_url;
				}
				$reserved_codes [] = $reserved_code;
				$merch = $merchants_info [$reserved_code->merchant->account_holder_id];
				// pr($merch);
				// exit;
				if ($merch->requires_shipping) {
					if ($order_id == 0) {
						throw new InvalidArgumentException ( "Shipping address must be provided, selected merchant requires shipping." );
					}
					// add the code as a line item to the order
					PhysicalOrder::add_line_item ( ( int ) $reserved_code->id, $order_id );
				} elseif ($merch->physical_order) {
					$userData = User::read_by_id ( $user->account_holder_id );
					$shipToName = $userData->first_name . ' ' . $userData->last_name . ' ' . '(' . $merch->name . ')';
					$address = new stdClass ();
					$address->ship_to_name = $shipToName;
					$address->line_1 = 'N/A';
					$address->line_2 = 'N/A';
					$address->zip = 'N/A';
					$address->city = 'N/A';
					$address->user_id = $user->account_holder_id;
					$address->country_id = 232;
					$address->state_id = 1;
					$userData->sku_value = $reserved_code->sku_value;
					$userData->gift_code = $reserved_code->code;
					$note = json_encode ( $userData, JSON_HEX_APOS );
					$order_id = PhysicalOrder::create ( $user->account_holder_id, $program_id, $address, $note );
					PhysicalOrder::add_line_item ( ( int ) $reserved_code->id, $order_id );
				}

				if($merch->use_tango_api){
                    $tango_order = new stdClass ();
                    $tango_order->physical_order_id = $order_id;
                    $tango_order->program_id = $program_id;
                    $tango_order->user_id = $user->account_holder_id;
                    $tango_order->merchant_id = $merch->account_holder_id;
                    $tango_order->tango_order_external_id = null;
                    $tango_order->tango_order_created_at = null;
                    TangoOrder::create ($tango_order);
                }
			}
		}
		// pr($debug);
		// exit;
		$gift_codes_redeemed_for = array ();
		if (isset ( $redeem_merchant_info ) && count ( $redeem_merchant_info )) {
			foreach ( $redeem_merchant_info as $merchant_id => &$details ) {
				if (count ( $details )) {
					foreach ( $details as $code_value => &$values ) {
						// check and save how many codes is before redeem and store in table:
						$code_count_before = 0;
						// send alert if low inventory - save count before, use it later for check
						$redeemable_denominations = self::read_list_redeemable_denominations_by_merchant_and_sku_value ( ( int ) $merchant_id, ( float ) $code_value );
						if (is_array ( $redeemable_denominations ) && count ( $redeemable_denominations ) > 0) {
							foreach ( $redeemable_denominations as $redeemable_denomination ) {
								$code_count_before += $redeemable_denomination->count;
							}
						}
						$values ['count_before'] = $code_count_before;
						$values ['used'] = 0;
					}
				}
			}
		}

		return $denomination_list;

		return $response;



        return $all_merchants;
        return $merchantsCostToProgram;
        return $gift_codes;
    }
}
