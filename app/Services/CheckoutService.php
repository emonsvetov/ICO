<?php
namespace App\Services;

use App\Events\MerchantDenominationAlert;
use App\Events\OrderShippingRequest;
use App\Events\TangoOrderCreated;

use App\Services\Program\TangoVisaApiService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use App\Models\Traits\IdExtractor;
use App\Models\ExternalCallback;
use App\Models\PhysicalOrder;
use App\Models\OptimalValue;
use App\Models\TangoOrder;
use App\Models\Currency;
use App\Models\Giftcode;
use App\Models\Merchant;
use App\Models\Country;
use App\Models\State;
use Illuminate\Support\Facades\Http;

class CheckoutService
{
    use IdExtractor;

    private $tangoVisaApiService;

    public function __construct(TangoVisaApiService $tangoVisaApiService) {
        $this->tangoVisaApiService = $tangoVisaApiService;
    }

    public function processOrder($cart, $program, $user = null)
    {
		// return Merchant::getRoot( 6 );

		// pr($cart);

		// Note: There is some work TODO in this function. The order creation, external callbacks, and email alerts to be precise - Arvind

		// DB::statement("UNLOCK TABLES;");
		// return;

		// return $program->id;
		// return $program->program_is_invoice_for_awards ();

		$Logger = Log::channel('redemption');

		$response = [];
        $gift_codes = & $cart['items'];
		$order_address = !empty($cart['order_address']) ? (object) $cart['order_address'] : null;

		if( !$gift_codes ) return ['errors' => "No cart items in CheckoutService:processOrder"];

        if (!$user){
            $user = auth()->user();
        }

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
		$gift_code_provider_account_holder_id = [];
		$all_merchants = [];
		// Verify that the merchant and gift code denominations are valid
		// Also, total up the transaction
		// pr($gift_codes);
		// return;
		$merchant_ids = [];

		/****** Test code BOF */
		// return OptimalValue::readByMerchanIdAndDenomination ( 1, (float) 23);
		// $merchant = Merchant::find(1);
		// self::_merchant_denomination_alert ( 'inimist@gmail.com', $merchant, 2, 40, 23 );
		/****** Test code EOF */

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

        foreach ( $gift_codes as &$gift_code ) {
			// $gift_code = (object) $gift_code_array;
            $gift_code = (object) $gift_code;
            $redemptionValue = 0;
			$redemptionFee = 0;
            $all_merchants[$gift_code->merchant_id] = $merchant = get_merchant_by_id($merchants, $gift_code->merchant_id);

            $gift_code->merchant_account_holder_id = $merchant->account_holder_id;

			// pr($merchant);
            // exit;

            if (in_array ( $merchant->id, $merchantsCostToProgram )) {
                $where = [
                    'purchased_by_v2' => 0
                ];

                if( GiftcodeService::isTestMode($program) ){
                    $where['medium_info_is_test'] = 1;
                }else{
                    $where['medium_info_is_test'] = 0;
                }

				$gift_code_values_response = Giftcode::getRedeemableListByMerchant ( $merchant, $where );
				//pr($gift_code_values_response);
				foreach ( $gift_code_values_response as $giftCode ) {
					$skuValue = ( int ) $giftCode->sku_value;
					$cartSkuValue = ( int ) $gift_code->sku_value;
                    // $Logger->info("\$cartSkuValue:$cartSkuValue");
                    // $Logger->info("\$skuValue:$skuValue");
                    // $Logger->info(json_encode($giftCode->toArray()));
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

            // $Logger->info("\$redemption_value_total:$redemption_value_total");


            $merchants_info[$merchant->account_holder_id] = $merchant;
			if ($merchant->get_gift_codes_from_root) {
                $topMerchant = Merchant::get_top_level_merchant ( $gift_code->merchant_id ) ;
				$gift_code->gift_code_provider_account_holder_id = $topMerchant->account_holder_id ;
				// $topMerchant = Merchant::read( $gift_code->gift_code_provider_account_holder_id );
			    $merchants_info [$topMerchant->account_holder_id] = $topMerchant;
			} else {
				$gift_code->gift_code_provider_account_holder_id = $gift_code->merchant_account_holder_id;
			}

			$gift_code_provider_account_holder_ids[$gift_code->merchant_account_holder_id] = $gift_code->gift_code_provider_account_holder_id;
			//pr($gift_code->gift_code_provider_account_holder_id);exit;
			// If the option for "website is redemption url" store this merchant's website on the shopping cart item for later use
			if ($merchant->website_is_redemption_url) {
				// update the gift code record
				$gift_code->redemption_url = $merchant->website;
			}
			// run this check against the gift_code_provider_account_holder_id
			$external_callbacks = ExternalCallback::read_list_by_type ( ( int ) $gift_code->gift_code_provider_account_holder_id, 'B2B Gift Code' );
			// pr($external_callbacks->toArray());
			// pr(count ( $external_callbacks ));
			// exit;
			if ( count ( $external_callbacks ) > 0 ) {
				//$debug['$external_callbacks'] = $external_callbacks;
				// Do not check this merchant's inventory level they use a callback to get giftcodes on the fly
				//Lets save for later use,
				$all_external_callbacks[$gift_code->gift_code_provider_account_holder_id] = $external_callbacks;
			} else {
				//$debug['no_external_callback'] = $external_callbacks;
				// Verify that there is enough inventory for this redemption value to complete this portion of the transaction
				// Billy added for cost to propgram
				// pr($merchant->account_holder_id);
				// pr($merchantsCostToProgram);
				// pr($gift_code->gift_code_provider_account_holder_id);
				// pr( in_array ( $merchant->account_holder_id, $merchantsCostToProgram ) );

                $where = [
                    'purchased_by_v2' => 0
                ];
                if( GiftcodeService::isTestMode($program) ){
                    $where = ['medium_info_is_test' => 1];
                }else{
                    $where = ['medium_info_is_test' => 0];
                }

				if (in_array ( $merchant->id, $merchantsCostToProgram )) {
					$denomination_list = GiftCode::getRedeemableListByMerchantAndRedemptionValue ( $gift_code->merchant_id, $redemptionValue, '', $where );
					if (! isset ( $denomination_list ) || count ( $denomination_list ) < 1) {
						// throw new RuntimeException ( 'Out of inventory' );
						$response['errors'][] = 'Out of inventory';
						return $response;
					}
					// Flag to indicate whether or not the redemption value was found in the denomination list
					$found_denomination = false;
					foreach ( $denomination_list as $denomination_info ) {
						if ($denomination_info->redemption_value == $redemptionValue && $denomination_info->sku_value == $gift_code->sku_value) {
							$found_denomination = true;
							if ($denomination_info->count < $gift_code->qty) {
								// throw new RuntimeException ( 'Insufficient inventory to complete the transaction' );
								$response['errors'][] = 'Insufficient inventory to complete the transaction';
								return $response;
							}
							break;
						}
					}
					if (! $found_denomination) {
						// throw new RuntimeException ( 'Out of inventory' );
						$response['errors'][] = 'Out of inventory';
						return $response;
					}
				} else {
				    $denomination_list = GiftCode::getRedeemableListByMerchantAndRedemptionValue ( $gift_code->merchant_id, $gift_code->redemption_value, '', $where );
					if (! isset ( $denomination_list ) || count ( $denomination_list ) < 1) {
						// throw new RuntimeException ( 'Out of inventory' );
						$response['errors'][] = 'Out of inventory';
						return $response;
					}
					// Flag to indicate whether or not the redemption value was found in the denomination list
					$found_denomination = false;
                    // pr($denomination_list->toArray());
                    // pr( $gift_code );
                    // exit;
					foreach ( $denomination_list as $denomination_info ) {
						if ($denomination_info->redemption_value == $gift_code->redemption_value && $denomination_info->sku_value == $gift_code->sku_value) {
							$found_denomination = true;
							if ($denomination_info->count < $gift_code->qty) {
								// throw new RuntimeException ( 'Insufficient inventory to complete the transaction' );
								$response['errors'][] = 'Insufficient inventory to complete the transaction';
								return $response;
							}
							break;
						}
					}
					if (! $found_denomination) {
						// throw new RuntimeException ( 'Out of inventory' );
						$response['errors'][] = 'Out of inventory';
						return $response;
					}
				}
			}
        }

		$current_balance = $user->readAvailableBalance( $program, $user);

        // die($redemption_value_total);

        // $Logger->info("\$current_balance:$current_balance");
        // $Logger->info("\$redemption_value_total:$redemption_value_total");

		if ($current_balance < $redemption_value_total) {
			$response['errors'][] = 'Current ending balance of the user is insufficient to redeem for the gift code';
			return $response;
		}

		$order_id = 0;
		if ( $order_address ) {
			// NOT TESTED YET, FOR NOW ASSUMING THAT IT IS NOT A PhysicalOrder
			$order_id = PhysicalOrder::create ( $user->account_holder_id, $program->id, $order_address );
		}

		// return $merchantsCostToProgram;

		$currency_id = Currency::getIdByType(config('global.default_currency'), true);

		$reserved_codes = array ();
		$redeem_merchant_info = array ();
		// pr($gift_code_provider_account_holder_ids);
		// pr($all_external_callbacks);
		foreach ( $gift_codes as $gift_code2_array ) {
			$gift_code2 = (object) $gift_code2_array;

			// pr($gift_code2);
			// exit;

			$gift_code2->gift_code_provider_account_holder_id = $gift_code_provider_account_holder_ids[$gift_code2->merchant_account_holder_id];

			for($i = 0; $i < $gift_code2->qty; ++ $i) {
				// NOT IMPLEMENTED YET
				// Need some work on Giftcode::_run_gift_code_callback
				// Check to see if the merchant uses a b2b redemption callback, if it does make the callback now and acquire the giftcode
				//$external_callbacks = $this->external_callbacks_model->read_list_by_type ( ( int ) $gift_code2->gift_code_provider_account_holder_id, 'B2B Gift Code' );
				$external_callbacks = isset($all_external_callbacks[$gift_code2->gift_code_provider_account_holder_id]) ? $all_external_callbacks[$gift_code2->gift_code_provider_account_holder_id] : null ;
				//TODO!!! To run callback and create the gift code in case of external callback is pending: Arvind, 19th May 2022
				// This section is TODO
				if ( $external_callbacks && count ( $external_callbacks ) > 0) {
					$data = array ();
					$data ['amount'] = ( float ) $gift_code2->redemption_value;
					$cb_response = Giftcode::_run_gift_code_callback ( $external_callbacks [0], $program->id, $user->account_holder_id, ( int ) $gift_code2->gift_code_provider_account_holder_id, $data );
					if( !empty($cb_response['errors']))	{
						$response['errors'][] = $cb_response['errors'];
						return $response;
					}
					if ($cb_response->response_code != '200') {
						$response['errors'][] = 'Error encountered when calling B2B Gift Code callback. ' . $cb_response->response_data;
						return $response;
					}
					$code = $cb_response->data;
					// Add the giftcode to the merchant's inventory
					$gift_code_id = ( int ) Giftcode::createGiftcode ( ( int ) $user->id, ( int ) $gift_code2->merchant_id, $code );
					// Read the rest of the information about the code that was reserved
					$reserved_code = Giftcode::readGiftcodeByMerchantAndId ( ( int ) $gift_code2->gift_code_provider_account_holder_id, $gift_code_id );
				} else {
					// merchant hasn't external callback so store all values
					// store all values to redeem $redeem_merchant_info[merchant_id][code_value]
					$redeem_merchant_info [$gift_code2->merchant_id] [number_format ( $gift_code2->sku_value, 2 )] = array ();

					$reserved_code = Giftcode::holdGiftcode ([
						'user_account_holder_id' => $user->account_holder_id,
						'program' => $program,
						'merchant_account_holder_id' => $gift_code2->gift_code_provider_account_holder_id,
						'redemption_value' => $gift_code2->redemption_value,
						'sku_value' => $gift_code2->sku_value,
						'merchants' => $merchants->toArray(),
						'merchant_id' => $gift_code2->merchant_id,
					]);
				}

				if (! isset ( $reserved_code )) {
					$response['errors'][] = "Unable to reserve GiftCode. {$gift_code2->merchant_id}";
					return $response;
				}

				if( isset($reserved_code['errors']) )	{
					$response['errors'][] = $reserved_code['errors'];
					return $response;
				}

				$reserved_code->gift_code_provider_account_holder_id = ( int ) $gift_code2->gift_code_provider_account_holder_id;
				if (! isset ( $reserved_code->merchant )) {
					$reserved_code->merchant = isset( $all_merchants[$gift_code2->merchant_id] ) ?
                                                      $all_merchants[$gift_code2->merchant_id] :
                                                      $all_merchants[$gift_code2->merchant_id] = Merchant::find($gift_code2->merchant_id); //Beware, inline assignment!
				}

				// exit;
				// $reserved_code->merchant->account_holder_id = $gift_code2->merchant_id;
				// If the shopping cart item has a redemption url set, it means that the merchant that this code will be redeemed from
				// has the "website is redemption url" option turned on.
				// if (isset ( $gift_code2->redemption_url ) && $gift_code2->redemption_url != '') {
					// $reserved_code->redemption_url = $gift_code2->redemption_url;
				// }

				$merch = $merchants_info [$reserved_code->gift_code_provider_account_holder_id];
				$reserved_code->merchant->toa_id = $merch->toa_id;
				$reserved_code->merchant->virtual_denominations = $merch->virtual_denominations;
				$reserved_code->merchant->merchant_code = $merch->merchant_code;
				$reserved_code->redeemed_merchant = $merch;
				$reserved_codes [] = $reserved_code;

				// exit;
				if ($merch->requires_shipping) {
					if ($order_id == 0) {
						// throw new InvalidArgumentException ( "Shipping address must be provided, selected merchant requires shipping." );
						$response['errors'][] = sprintf("Shipping address must be provided, selected merchant requires shipping. Merchant:%s", $merch->name);
						return $response;
					}
					// add the code as a line item to the order
					PhysicalOrder::add_line_item ( ( int ) $reserved_code->id, $order_id );
				} elseif ($merch->physical_order ) {
					$shipToName = $user->first_name . ' ' . $user->last_name . ' ' . '(' . $merch->name . ')';
					$address = new \stdClass ();
					$userData = new \stdClass ();
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
					$order_id = PhysicalOrder::create ( $user->id, $program->id, $address, $note );
					PhysicalOrder::add_line_item ( ( int ) $reserved_code->id, $order_id );
				}

				// TODO ; TangoOrder setup is pending in rebuild

				if($merch->use_tango_api && !$merch->use_virtual_inventory){
					$tango_order = new \stdClass ();
					$tango_order->physical_order_id = $order_id;
					$tango_order->program_id = $program->id;
					$tango_order->user_id = $user->id;
					$tango_order->merchant_id = (int)$merch->id;
					$tango_order->reference_order_id = null;
					$tangoOrderId = TangoOrder::create ((array)$tango_order);
					event( new TangoOrderCreated( $tangoOrderId ) );
                }
			}
		}
		// pr($debug);
		// exit;
		$gift_codes_redeemed_for = [];
		if (isset ( $redeem_merchant_info ) && count ( $redeem_merchant_info )) {
			foreach ( $redeem_merchant_info as $merchant_id => &$details ) {
				if (count ( $details )) {
					foreach ( $details as $code_value => &$values ) {
						// check and save how many codes is before redeem and store in table:
						$code_count_before = 0;
						// send alert if low inventory - save count before, use it later for check
						$redeemable_denominations = ( new \App\Services\GiftcodeService )->getRedeemableListByMerchantAndSkuValue ( ( int ) $merchant_id, ( float ) $code_value );
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
		try {
			// I am not sure why some of the database transactions above are exempted from the rollback. Probably we need to move the DB::beginTransaction(); to the very top of this function - Arvind
			DB::statement("LOCK TABLES postings WRITE, medium_info WRITE, journal_events WRITE;");
			DB::beginTransaction();
			$commit = true;
			// $currency_type = self::$currency_type;
			//pr($debug);
			foreach ( $reserved_codes as $code ) {
				// return $code;
				$gift_code_id = ( int ) $code->id;
				// format the gift code details
				// If gift_code_provider_account_holder_id != merchant_id, perform a gift code transfer before redeeming
				if ($code->merchant->account_holder_id != $code->gift_code_provider_account_holder_id) {
					$this->_transferGiftcodesToMerchantNoTransaction([
						'user' => $user,
						'code' => $code,
						'currency_id' => $currency_id
					]);
				}
				// Added by Jay to add Premium cost to Program
				if (in_array ( $code->merchant->id, $merchantsCostToProgram )) {
                    $points_to_redeem = number_format ( ( float ) $code->sku_value, 4, '.', '' );
                } else {
                    $points_to_redeem = number_format ( ( float ) $code->redemption_value, 4, '.', '' );
                }

				// construct SQL statement to redeem gift codes
				if ($program->program_is_invoice_for_awards ()) {
					$result = $this->_redeemPointsForGiftcodesNoTransaction([
						'points_to_redeem' => $points_to_redeem,
						'code' => $code,
						'user' => $user,
						'program' => $program,
						'owner_id' => $owner_id,
						'currency_id' => $currency_id,
					]);
				} else {
					$result = $this->_redeemMoniesForGiftcodesNoTransaction([
						'points_to_redeem' => $points_to_redeem,
						'code' => $code,
						'user' => $user,
						'program' => $program,
						'owner_id' => $owner_id,
						'currency_id' => $currency_id,
					]);
				}

				$response['redeem_result'] = $result;

				if( !empty($result['success']) )	{
					$journalId = $result['journal_event_id'];
				}	else	{
					DB::rollaback();
					DB::statement("UNLOCK TABLES;");
					$response['errors'][] = "Could not redeem";
					return $response;
				}

				// BIlly added to add Premium cost to Program
				if (in_array ( $code->merchant->id, $merchantsCostToProgram )) {
					Giftcode::handlePremiumDiff( [
						'code' => $code,
						'journal_event_id' => $journalId,
					]);
				}
				// --End billy added -------------------

				if (! isset ( $redeem_merchant_info [$code->merchant->id] )) {
					$redeem_merchant_info[$code->merchant->id][number_format ( $code->sku_value, 2 )]['used'] = 0;
				}
				$redeem_merchant_info[$code->merchant->id][number_format ( $code->sku_value, 2 )]['used']++;
				$gift_codes_redeemed_for [] = $code;
			}
		} catch ( \Exception $e ) {
			DB::rollback();
			DB::statement("UNLOCK TABLES;");
			$commit = false;
			$response['errors'][] = 'An error occurred while processing this transaction';
			return $response;
		}

		if ( $commit ){
            $response['success'] = true;
            $response['gift_codes_redeemed_for'] = $gift_codes_redeemed_for;
            DB::commit();
            DB::statement("UNLOCK TABLES;");


            $Logger->info("before sync:");

            // purchase codes from Tango since all transactions are final
            foreach($reserved_codes as $code){

                $gift_code_id = ( int )$code->id;

                if(!$code->virtual_inventory &&
                    $code->merchant->v2_merchant_id &&
                    env('V2_GIFTCODE_SYNC_ENABLE')){

                     DB::table(MEDIUM_INFO)
                            ->where('id', $code->id)
                            ->update(['v2_sync_status' => Giftcode::SYNC_STATUS_REQUIRED]);
                }

                if($code->virtual_inventory){
                    $data = [
                        'amount' => $code->sku_value,
                        'sendEmail' => false,
                        'message' => 'Congratulations on your Reward!',
                        'notes' => 'auto generated order',
                        'externalRefID' => 'V3' . $code->id
                    ];

                    $toa_utid = null;
                    $denominations = array_map('trim', explode(',', $code->merchant->virtual_denominations));
                    foreach($denominations as $denomination){
                        $pieces = array_map('trim', explode(':', $denomination));
                        if(count($pieces) == 3){
                            list($utid, $sku_value, $redemption_value) = $pieces;
                            if($redemption_value == $data['amount']){
                                $toa_utid = $utid;
                                break;
                            }
                        }
                    }

                    $Logger->info('code: ' . print_r($code, true) );


                    $tangoResult = $this->tangoVisaApiService->submit_order($data, $code->merchant->toa_id, $toa_utid, $code->merchant->merchant_code);

                    $Logger->info('gift_code_id: ' . $gift_code_id );
                    $Logger->info('merchant code: ' . $code->merchant->merchant_code );
                    $Logger->info('Tango logs: ' . print_r($tangoResult, true) );

                    if(isset($tangoResult['referenceOrderID']) && $tangoResult['referenceOrderID']){
                        DB::table(MEDIUM_INFO)
                                ->where('id', $code->id)
                                ->update([
                                    'code' =>  $tangoResult['code'],
                                    'pin' =>  $tangoResult['pin'],
                                    'tango_reference_order_id' => $tangoResult['referenceOrderID']
                                ]);
                    }else{
                        DB::table(MEDIUM_INFO)
                                ->where('id', $code->id)
                                ->update([
                                    'tango_request_id' => $tangoResult['requestId'],
                                ]);
                    }

                    foreach($gift_codes_redeemed_for as $index => $gift_codes_redeemed_item){
                        if($gift_codes_redeemed_item->id == $gift_code_id){
                            $gift_codes_redeemed_for[$index]->pin = $tangoResult['pin'];
                            $gift_codes_redeemed_for[$index]->code = $tangoResult['code'];
                            $gift_codes_redeemed_for[$index]->tango_reference_order_id = $tangoResult['referenceOrderID'];
                            break;
                        }
                    }
                }
            }
            $response['gift_codes_redeemed_for'] = $gift_codes_redeemed_for;
        }

		if (isset ( $order_address ) && is_object ( $order_address )) {
			// $user_info = $user->toArray();
			$mail_to = "support@incentco.com";
			switch ( \App::environment() ) {
				case "production" :
					$mail_to = "support@incentco.com";
					// $mail_to = "arvind@inimisttech.com";
					break;
				case "staging" :
					$mail_to = "bmorse@incentco.com";
					// $mail_to = "arvind@inimisttech.com";
					break;
				default :
					// $mail_to = "bmorse@incentco.com";
					$mail_to = "arvind@inimisttech.com";
			}
			$ship_to_state = State::find ( ( int ) $order_address->state_id );
			$ship_to_country = Country::find ( ( int ) $order_address->country_id );

			$data = [
				'order_id' => $order_id,
				'order_address' => $order_address,
				'user_info' => $user,
				'ship_to_state' => $ship_to_state,
				'ship_to_country' => $ship_to_country,
			];

			try {
				event( new OrderShippingRequest($data, $order_id) );
			}   catch(\Exception $e) {
				$response['errors'][] = 'Error sending OrderShippingRequest notification with error:' . $e->getMessage() . ' in line ' . $e->getLine();
				return $response;
			}
		}
		// all saved so check code count now
		if (isset ( $redeem_merchant_info ) && count ( $redeem_merchant_info )) {
			$percentage_alerts = array (
					0,
					25,
					50
			);
			$alerts_to_send = array ();
			foreach ( $redeem_merchant_info as $merchant_id => &$details ) {
				foreach ( $details as $code_value => $values ) { // check every sku_value redeemed
				  // find all optimal values for code value
					$optimal_values = OptimalValue::readByMerchanIdAndDenomination ( ( int ) $merchant_id, ( float ) $code_value );
					if (count ( $optimal_values ) > 0) {
						$alert_counts = array ();
						$count_after = $values ['count_before'] - $values ['used'];
						foreach ( $optimal_values as $optimal_value ) {
							foreach ( $percentage_alerts as $percent ) {
								// find amount that fits percentage value
								$alert_count = ($optimal_value->optimal_amount / 100) * $percent;
								if ($values ['count_before'] > $alert_count && $count_after <= $alert_count) { // value was greater before but now is below or equal so send alert to merchant
									$alerts_to_send [] = array (
											'merchant_id' => $merchant_id,
											'percentage_alert_value' => $percent,
											'code_count' => $count_after,
											'code_value' => $code_value
									);
								}
							}
						}
					}
				}
			}
			if (count ( $alerts_to_send ) > 0) {
				foreach ( $alerts_to_send as $alert ) {
					// send all alerts collected before
					$merchant = ($_merchant = get_merchant_by_id($merchants, $alert['merchant_id'])) ? $_merchant : Merchant::find($alert['merchant_id']);

					self::_merchant_denomination_alert ( config('global.default_email'), $merchant, $alert['code_count'], $alert['percentage_alert_value'], $alert['code_value'] );
				}
			}
		}
		return $response;
    }

	private function _redeemPointsForGiftcodesNoTransaction( array $data )	{
		if( empty($data['points_to_redeem']) || empty($data['code']) || empty($data['user']) || empty($data['program']) || empty($data['owner_id'] ) )	{
			return ['errors' => sprintf('Invalid data passed to CheckoutService::_redeemPointsForGiftcodesNoTransaction')];
		}
		if( empty($data['currency_id']))	{
			$data['currency_id'] = Currency::getIdByType(config('global.default_currency'), true);
		}
		return Giftcode::redeemPointsForGiftcodesNoTransaction( $data );
	}

	private function _redeemMoniesForGiftcodesNoTransaction( array $data )	{
		if( empty($data['points_to_redeem']) || empty($data['code']) || empty($data['user']) || empty($data['program']) || empty($data['owner_id'] ) )	{
			return ['errors' => sprintf('Invalid data passed to CheckoutService::_redeemMoniesForGiftcodesNoTransaction')];
		}
		if( empty($data['currency_id']))	{
			$data['currency_id'] = Currency::getIdByType(config('global.default_currency'), true);
		}
		return Giftcode::redeemMoniesForGiftcodesNoTransaction( $data );
	}

	private function _transferGiftcodesToMerchantNoTransaction( array $data )	{
		if( empty($data['code']) || empty($data['user']) )	{
			return ['errors' => sprintf('Invalid data passed to CheckoutService::_transferGiftcodesToMerchantNoTransaction')];
		}
		if( empty($data['currency_id']))	{
			$data['currency_id'] = Currency::getIdByType(config('global.default_currency'), true);
		}
		return Giftcode::transferGiftcodesToMerchantNoTransaction( $data );
	}

	private function _merchant_denomination_alert($email = '', $merchant, $code_count = 0, $code_percentage = 0, $redemption_value = 0.0) {
		if( !$email ) $email = config('global.default_email');
		$data = [
			'merchant' => $merchant,
			'code_count' => $code_count,
			'code_percentage' => $code_percentage,
			'redemption_value' => $redemption_value
		];

		try {
			event( new MerchantDenominationAlert($data) );
			return true;
        }   catch(Exception $e) {
            return ['errors' => 'Error sending MerchantDenominationAlert with error:' . $e->getMessage() . ' in line ' . $e->getLine()];
        }
	}
}
