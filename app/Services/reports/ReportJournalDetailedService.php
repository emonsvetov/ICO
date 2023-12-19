<?php
namespace App\Services\reports;

use App\Models\JournalEventType;
use App\Models\AccountType;
use App\Models\Program;

class ReportJournalDetailedService extends ReportServiceAbstract
{
    protected $reportFactory;

	protected function calcByDateRange( $params = [] )
    {
		// Setup the default params for the sub reports
		$subreport_params = array ();
		$subreport_params [self::DATE_FROM] = $params [self::DATE_FROM];
		$subreport_params [self::DATE_TO] = $params [self::DATE_TO];

		if (is_array ( $this->params[self::PROGRAMS] ) && count ( $this->params[self::PROGRAMS] ) > 0) {
            // dd($this->params [self::PROGRAMS]);
			$ranked_programs = Program::read_programs ( $this->params [self::PROGRAMS], true );
            // dd($ranked_programs->pluck('account_holder_id'));
			if ( $ranked_programs->isNotEmpty() ) {
				$account_holder_ids = [];
				$defaultValues = [
					'fixed_fee' => 0,
					'setup_fee' => 0,
					'admin_fee' => 0,
					'usage_fee' => 0,
					'deposit_fee' => 0,
					'deposit_reversal' => 0,
					'deposit_fee_reversal' => 0,
					'transaction_fee' => 0,
					'refunded_transaction_fee' => 0,
					'deposit_reversal' => 0,
					'deposit_fee_reversal' => 0,
					'program_funds_net_transfers' => 0,
					'program_refunds_for_monies_pending' => 0,
					'deposits' => 0,
					'points_purchased' => 0,
					'points_redeemed' => 0,
					'reclaims' => 0,
					'award_credit_reclaims' => 0,
					'discount_rebate_credited_to_program' => 0,
					'total_spend_rebate' => 0,
					'expiration_rebate_credited_to_program' => 0,
					'codes_redeemed_cost' => 0,
					'codes_redeemed_premium' => 0,
					'convenience_fees' => 0,
					'premium_fee' => 0,
					'net_points_purchased' => 0,
					'program_funds_net_transfers' => 0,
					'program_refunds_for_monies_pending' => 0
				];
				foreach ( $ranked_programs as $program ) {
					array_push($account_holder_ids, $program->account_holder_id);
					$this->table[$program->account_holder_id] = $program;
					foreach($defaultValues as $key=>$value)	{
						$this->table[$program->account_holder_id]->setAttribute($key, $value);
					}
				}
				// Get all types of fees, etc where we are interested in them being credits, fees from both award types are the transaction fees, they will be grouped by type, so we can pick which one we want
				$subreport_params [self::ACCOUNT_HOLDER_IDS] = $account_holder_ids;
				$subreport_params [self::PROGRAMS] = $this->params [self::PROGRAMS];
				$subreport_params [self::IS_CREDIT] = 1;
				$subreport_params [self::ACCOUNT_TYPES] = array (
					AccountType::ACCOUNT_TYPE_MONIES_FEES,
					AccountType::ACCOUNT_TYPE_MONIES_DUE_TO_OWNER,
					AccountType::ACCOUNT_TYPE_MONIES_AVAILABLE,
					AccountType::ACCOUNT_TYPE_MONIES_SHARED,
					AccountType::ACCOUNT_TYPE_INTERNAL_STORE_POINTS,
					AccountType::ACCOUNT_TYPE_PROMOTIONAL_POINTS
				);
				$subreport_params [self::JOURNAL_EVENT_TYPES] = [
					JournalEventType::JOURNAL_EVENT_TYPES_AWARD_POINTS_TO_RECIPIENT,
					JournalEventType::JOURNAL_EVENT_TYPES_AWARD_MONIES_TO_RECIPIENT,
					JournalEventType::JOURNAL_EVENT_TYPES_REFUND_PROGRAM_FOR_MONIES_TRANSACTION_FEE,
					JournalEventType::JOURNAL_EVENT_TYPES_REFUND_PROGRAM_FOR_POINTS_TRANSACTION_FEE,
					JournalEventType::JOURNAL_EVENT_TYPES_CHARGE_FIXED_FEE,
					JournalEventType::JOURNAL_EVENT_TYPES_CHARGE_SETUP_FEE,
					JournalEventType::JOURNAL_EVENT_TYPES_CHARGE_MONTHLY_USAGE_FEE,
					JournalEventType::JOURNAL_EVENT_TYPES_CHARGE_ADMIN_FEE,
					JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_DEPOSIT_FEE,
					JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_MONIES_PENDING,
					JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_CONVENIENCE_FEE,
					JournalEventType::JOURNAL_EVENT_TYPES_REDEEM_POINTS_FOR_GIFT_CODES,
					JournalEventType::JOURNAL_EVENT_TYPES_REDEEM_POINTS_FOR_INTERNATIONAL_SHOPPING,
					JournalEventType::JOURNAL_EVENT_TYPES_REDEEM_MONIES_FOR_GIFT_CODES,
					JournalEventType::JOURNAL_EVENT_TYPES_EXPIRE_POINTS,
					JournalEventType::JOURNAL_EVENT_TYPES_EXPIRE_MONIES,
					JournalEventType::JOURNAL_EVENT_TYPES_DEACTIVATE_POINTS,
					JournalEventType::JOURNAL_EVENT_TYPES_DEACTIVATE_MONIES,
					JournalEventType::JOURNAL_EVENT_TYPES_RECLAIM_POINTS,
                    JournalEventType::JOURNAL_EVENT_TYPES_AWARD_CREDIT_RECLAIM_POINTS,
					JournalEventType::JOURNAL_EVENT_TYPES_RECLAIM_MONIES,
					JournalEventType::JOURNAL_EVENT_TYPES_AWARD_CREDIT_RECLAIM_MONIES,
					JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_TOTAL_SPEND_REBATE,
					JournalEventType::JOURNAL_EVENT_TYPES_REDEEMABLE_ON_INTERNAL_STORE,
					JournalEventType::JOURNAL_EVENT_TYPES_PROMOTIONAL_AWARD
				];
                // pr($subreport_params);
                // exit;
                $this->reportFactory = new \App\Services\reports\ReportFactory();
                $credit_report = $this->reportFactory->build("SumPostsByAccountAndJournalEventAndCredit", $subreport_params);
                $credits_report_table = $credit_report->getReport();

				if (is_array ( $credits_report_table ) && count ( $credits_report_table ) > 0) {
					foreach ( $credits_report_table as $program_account_holder_id => $programs_credits_report_table ) {
						// Get an easier reference to the program
						$program = $this->table [$program_account_holder_id];
						if (is_array ( $programs_credits_report_table ) && count ( $programs_credits_report_table ) > 0) {
							foreach ( $programs_credits_report_table as $account_type_name => $account ) {
								if (is_array ( $account ) && count ( $account ) > 0) {
									foreach ( $account as $journal_event_type => $amount ) {
                                        $amount = number_format((float)$amount, 2, '.', '');
										switch ($account_type_name) {
											case AccountType::ACCOUNT_TYPE_INTERNAL_STORE_POINTS :
											case AccountType::ACCOUNT_TYPE_PROMOTIONAL_POINTS :
												switch ($journal_event_type) {
													case JournalEventType::JOURNAL_EVENT_TYPES_AWARD_POINTS_TO_RECIPIENT :
													case JournalEventType::JOURNAL_EVENT_TYPES_AWARD_MONIES_TO_RECIPIENT :
													case JournalEventType::JOURNAL_EVENT_TYPES_REDEEMABLE_ON_INTERNAL_STORE :
													case JournalEventType::JOURNAL_EVENT_TYPES_PROMOTIONAL_AWARD :
														$this->table[$program->account_holder_id]->points_purchased = $amount;
														break;
												}
												break;
											case AccountType::ACCOUNT_TYPE_MONIES_FEES :
												switch ($journal_event_type) {
													case JournalEventType::JOURNAL_EVENT_TYPES_AWARD_POINTS_TO_RECIPIENT :
														if ($program->invoice_for_awards) {
															$this->table[$program->account_holder_id]->transaction_fee = $amount;
														}
														break;
													case JournalEventType::JOURNAL_EVENT_TYPES_AWARD_MONIES_TO_RECIPIENT :
														if (! $program->invoice_for_awards) {
															$this->table[$program->account_holder_id]->transaction_fee = $amount;
														}
														break;
													case JournalEventType::JOURNAL_EVENT_TYPES_CHARGE_FIXED_FEE :
														$this->table[$program->account_holder_id]->fixed_fee = $amount;
														break;
													case JournalEventType::JOURNAL_EVENT_TYPES_CHARGE_SETUP_FEE :
														$this->table[$program->account_holder_id]->setup_fee = $amount;
														break;
													case JournalEventType::JOURNAL_EVENT_TYPES_CHARGE_ADMIN_FEE :
														$this->table[$program->account_holder_id]->admin_fee = $amount;
														break;
													case JournalEventType::JOURNAL_EVENT_TYPES_CHARGE_MONTHLY_USAGE_FEE :
														$this->table[$program->account_holder_id]->usage_fee = $amount;
														break;
												}
												break;
											case AccountType::ACCOUNT_TYPE_MONIES_DUE_TO_OWNER :
												switch ($journal_event_type) {
													case JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_DEPOSIT_FEE :
														$this->table[$program->account_holder_id]->deposit_fee = $amount;
														break;
													case JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_CONVENIENCE_FEE :
														$this->table[$program->account_holder_id]->convenience_fees = $amount;
														break;
													case JournalEventType::JOURNAL_EVENT_TYPES_REFUND_PROGRAM_FOR_POINTS_TRANSACTION_FEE :
														$this->table[$program->account_holder_id]->refunded_transaction_fee = $amount;
														break;
													case JournalEventType::JOURNAL_EVENT_TYPES_RECLAIM_POINTS :
														$this->table[$program->account_holder_id]->reclaims = $amount;
														break;
                                                    case JournalEventType::JOURNAL_EVENT_TYPES_AWARD_CREDIT_RECLAIM_POINTS :
                                                        $this->table [$program->account_holder_id]->award_credit_reclaims = $amount;
                                                        break;
												}
												break;
											case AccountType::ACCOUNT_TYPE_MONIES_AVAILABLE :
												switch ($journal_event_type) {
													case JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_MONIES_PENDING :
														$this->table[$program->account_holder_id]->deposits = $amount;
														break;
													case JournalEventType::JOURNAL_EVENT_TYPES_RECLAIM_MONIES :
														$this->table[$program->account_holder_id]->reclaims = $amount;
														break;
													case JournalEventType::JOURNAL_EVENT_TYPES_REFUND_PROGRAM_FOR_MONIES_TRANSACTION_FEE :
														$this->table[$program->account_holder_id]->refunded_transaction_fee = $amount;
														break;
												}
												break;
											case AccountType::ACCOUNT_TYPE_MONIES_SHARED :
												switch ($journal_event_type) {
													case JournalEventType::JOURNAL_EVENT_TYPES_REDEEM_POINTS_FOR_GIFT_CODES :
													case JournalEventType::JOURNAL_EVENT_TYPES_REDEEM_POINTS_FOR_INTERNATIONAL_SHOPPING :
														if ($program->invoice_for_awards) {
															$this->table[$program->account_holder_id]->discount_rebate_credited_to_program = $amount;
														}
														break;
													case JournalEventType::JOURNAL_EVENT_TYPES_REDEEM_MONIES_FOR_GIFT_CODES :
														if (! $program->invoice_for_awards) {
															$this->table[$program->account_holder_id]->discount_rebate_credited_to_program = $amount;
														}
														break;
													case JournalEventType::JOURNAL_EVENT_TYPES_EXPIRE_POINTS :
														if ($program->invoice_for_awards) {
															$this->table[$program->account_holder_id]->expiration_rebate_credited_to_program += $amount; // Add so expire and deactive are summed
														}
														break;
													case JournalEventType::JOURNAL_EVENT_TYPES_EXPIRE_MONIES :
														if (! $program->invoice_for_awards) {
															$this->table[$program->account_holder_id]->expiration_rebate_credited_to_program += $amount; // Add so expire and deactive are summed
														}
														break;
													case JournalEventType::JOURNAL_EVENT_TYPES_DEACTIVATE_POINTS :
														if ($program->invoice_for_awards) {
															$this->table[$program->account_holder_id]->expiration_rebate_credited_to_program += $amount; // Add so expire and deactive are summed
														}
														break;
													case JournalEventType::JOURNAL_EVENT_TYPES_DEACTIVATE_MONIES :
														if (! $program->invoice_for_awards) {
															$this->table[$program->account_holder_id]->expiration_rebate_credited_to_program += $amount; // Add so expire and deactive are summed
														}
														break;
													case JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_TOTAL_SPEND_REBATE :
														$this->table[$program->account_holder_id]->total_spend_rebate = $amount;
														break;
												}
											break;
										}
									}
								}
							}
						}
					}
				}
				// TODO: Include deposit reversals and subtract from the deposits row
				// Get all types of fees, etc where we are interested in them being debits
				$subreport_params[self::IS_CREDIT] = 0;
				$subreport_params[self::ACCOUNT_TYPES] = array (
					AccountType::ACCOUNT_TYPE_POINTS_REDEEMED,
					AccountType::ACCOUNT_TYPE_MONIES_REDEEMED,
					AccountType::ACCOUNT_TYPE_MONIES_AVAILABLE,
					AccountType::ACCOUNT_TYPE_MONIES_DUE_TO_OWNER
				);
				$subreport_params[self::JOURNAL_EVENT_TYPES] = array (
					JournalEventType::JOURNAL_EVENT_TYPES_REDEEM_POINTS_FOR_GIFT_CODES,
					JournalEventType::JOURNAL_EVENT_TYPES_REDEEM_POINTS_FOR_INTERNATIONAL_SHOPPING,
					JournalEventType::JOURNAL_EVENT_TYPES_REDEEM_MONIES_FOR_GIFT_CODES,
					JournalEventType::JOURNAL_EVENT_TYPES_REVERSAL_PROGRAM_PAYS_FOR_POINTS,
					JournalEventType::JOURNAL_EVENT_TYPES_REVERSAL_PROGRAM_PAYS_FOR_MONIES_PENDING,
					JournalEventType::JOURNAL_EVENT_TYPES_REVERSAL_PROGRAM_PAYS_FOR_SETUP_FEE,
					JournalEventType::JOURNAL_EVENT_TYPES_REVERSAL_PROGRAM_PAYS_FOR_FIXED_FEE,
					JournalEventType::JOURNAL_EVENT_TYPES_REVERSAL_PROGRAM_PAYS_FOR_MONTHLY_USAGE_FEE,
					JournalEventType::JOURNAL_EVENT_TYPES_REVERSAL_PROGRAM_PAYS_FOR_DEPOSIT_FEE,
					JournalEventType::JOURNAL_EVENT_TYPES_REVERSAL_PROGRAM_PAYS_FOR_CONVENIENCE_FEE,
				);
                // dd("HERE");
				// $debits_report = new ReportServiceSumPostsByAccountAndJournalEventAndCredit ( $subreport_params );

                // pr($subreport_params);
                $debits_report = $this->reportFactory->build("SumPostsByAccountAndJournalEventAndCredit", $subreport_params);
                // pr($debits_report);
				$debits_report_table = $debits_report->getReport ();
                // pr($debits_report_table);
                // exit;

				// Sort the second set of fees
				if (is_array ( $debits_report_table ) && count ( $debits_report_table ) > 0) {
					foreach ( $debits_report_table as $program_account_holder_id => $programs_debits_report_table ) {
						// Get an easier reference to the program
						$program = $this->table [$program_account_holder_id];
						if (is_array ( $programs_debits_report_table ) && count ( $programs_debits_report_table ) > 0) {
							foreach ( $programs_debits_report_table as $account_type_name => $account ) {
								if (is_array ( $account ) && count ( $account ) > 0) {
									foreach ( $account as $journal_event_type => $amount ) {
                                        $amount = number_format((float)$amount, 2, '.', '');
										switch ($account_type_name) {
											case AccountType::ACCOUNT_TYPE_POINTS_REDEEMED :
											case AccountType::ACCOUNT_TYPE_MONIES_REDEEMED :
												switch ($journal_event_type) {
													case JournalEventType::JOURNAL_EVENT_TYPES_REDEEM_POINTS_FOR_GIFT_CODES :
													case JournalEventType::JOURNAL_EVENT_TYPES_REDEEM_POINTS_FOR_INTERNATIONAL_SHOPPING :
														if ($program->invoice_for_awards) {
															$this->table [( int ) $program->account_holder_id]->points_redeemed = $amount;
														}
													break;
													case JournalEventType::JOURNAL_EVENT_TYPES_REDEEM_MONIES_FOR_GIFT_CODES :
														if (! $program->invoice_for_awards) {
															$this->table [( int ) $program->account_holder_id]->points_redeemed = $amount;
														}
													break;
												}
												break;
											case AccountType::ACCOUNT_TYPE_MONIES_AVAILABLE :
												switch ($journal_event_type) {
													case JournalEventType::JOURNAL_EVENT_TYPES_REVERSAL_PROGRAM_PAYS_FOR_MONIES_PENDING :
														if (! $program->invoice_for_awards) {
															$this->table [( int ) $program->account_holder_id]->deposits -= $amount;
														}
													break;
												}
												break;
											case AccountType::ACCOUNT_TYPE_MONIES_DUE_TO_OWNER :
												switch ($journal_event_type) {
													case JournalEventType::JOURNAL_EVENT_TYPES_REVERSAL_PROGRAM_PAYS_FOR_POINTS :
														if ($program->invoice_for_awards) {
															// Not sure if we need this as we aren't including payments that were made..
															// $this->table[(int)$program->account_holder_id]->points_purchased -= $amount;
														}
													break;
                                                    case JournalEventType::JOURNAL_EVENT_TYPES_REVERSAL_PROGRAM_PAYS_FOR_SETUP_FEE:
                                                        $this->table[(int)$program->account_holder_id]->program_setup_fee -= $amount;
                                                        break;
                                                    case JournalEventType::JOURNAL_EVENT_TYPES_REVERSAL_PROGRAM_PAYS_FOR_FIXED_FEE:
                                                        $this->table[(int)$program->account_holder_id]->program_fixed_fee -= $amount;
//                                                        $this->table[(int)$program->account_holder_id]->fixed_fee_reversal = $amount;
                                                        break;
                                                    case JournalEventType::JOURNAL_EVENT_TYPES_REVERSAL_PROGRAM_PAYS_FOR_MONTHLY_USAGE_FEE:
                                                        $this->table[(int)$program->account_holder_id]->usage_fee -= $amount;
                                                        break;
                                                    case JournalEventType::JOURNAL_EVENT_TYPES_REVERSAL_PROGRAM_PAYS_FOR_DEPOSIT_FEE:
                                                        $this->table[(int)$program->account_holder_id]->deposit_fee -= $amount;
//                                                        $this->table[(int)$program->account_holder_id]->deposit_fee_reversal = $amount;
														break;
                                                    case JournalEventType::JOURNAL_EVENT_TYPES_REVERSAL_PROGRAM_PAYS_FOR_CONVENIENCE_FEE:
                                                        $this->table[(int)$program->account_holder_id]->convenience_fees -= $amount;
//                                                        $this->table[(int)$program->account_holder_id]->convenience_fee_reversal = $amount;
													break;
												}
											break;
										}
									}
								}
							}
						}
					}
				}
				// Get the cost of gift codes redeemed
				$subreport_params[self::JOURNAL_EVENT_TYPES] = array (
					JournalEventType::JOURNAL_EVENT_TYPES_REDEEM_POINTS_FOR_GIFT_CODES,
					JournalEventType::JOURNAL_EVENT_TYPES_REDEEM_POINTS_FOR_INTERNATIONAL_SHOPPING,
					JournalEventType::JOURNAL_EVENT_TYPES_REDEEM_MONIES_FOR_GIFT_CODES
				);
				// $cost_of_redeemed_report = new ReportServiceSumProgramCostOfGiftCodesRedeemed ( $subreport_params );
				// $cost_of_redeemed_report_table = $cost_of_redeemed_report->getTable ();
                $cost_of_redeemed_report = $this->reportFactory->build("SumProgramCostOfGiftCodesRedeemed", $subreport_params);
				$cost_of_redeemed_report_table = $cost_of_redeemed_report->getReport ();

				if (is_array ( $cost_of_redeemed_report_table ) && count ( $cost_of_redeemed_report_table ) > 0) {
					foreach ( $cost_of_redeemed_report_table as $program_account_holder_id => $programs_cost_of_redeemed_report_table ) {
						// Get an easier reference to the program
						$program = $this->table [$program_account_holder_id];
						if (is_array ( $programs_cost_of_redeemed_report_table ) && count ( $programs_cost_of_redeemed_report_table ) > 0) {
							foreach ( $programs_cost_of_redeemed_report_table as $account_type_name => $account ) {
								if (is_array ( $account ) && count ( $account ) > 0) {
									foreach ( $account as $journal_event_type => $sum_row ) {
										if (is_array ( $sum_row ) && count ( $sum_row ) > 0) {
											foreach ( $sum_row as $sum_type => $amount ) {
                                                $amount = number_format((float)$amount, 2, '.', '');
												switch ($sum_type) {
													case ReportSumProgramCostOfGiftCodesRedeemedService::FIELD_COST_BASIS :
														switch ($account_type_name) {
															case AccountType::ACCOUNT_TYPE_POINTS_REDEEMED :
															case AccountType::ACCOUNT_TYPE_MONIES_REDEEMED :
																switch ($journal_event_type) {
																	case JournalEventType::JOURNAL_EVENT_TYPES_REDEEM_POINTS_FOR_GIFT_CODES :
																	case JournalEventType::JOURNAL_EVENT_TYPES_REDEEM_POINTS_FOR_INTERNATIONAL_SHOPPING :
																		if ($program->invoice_for_awards) {
																			$this->table [( int ) $program->account_holder_id]->codes_redeemed_cost = $amount;
																		}
																		break;
																	case JournalEventType::JOURNAL_EVENT_TYPES_REDEEM_MONIES_FOR_GIFT_CODES :
																		if (! $program->invoice_for_awards) {
																			$this->table [( int ) $program->account_holder_id]->codes_redeemed_cost = $amount;
																		}
																		break;
																}
																break;
														}
														break;
													case ReportSumProgramCostOfGiftCodesRedeemedService::FIELD_PREMIUM :
														switch ($account_type_name) {
															case AccountType::ACCOUNT_TYPE_POINTS_REDEEMED :
															case AccountType::ACCOUNT_TYPE_MONIES_REDEEMED :
																switch ($journal_event_type) {
																	case JournalEventType::JOURNAL_EVENT_TYPES_REDEEM_POINTS_FOR_GIFT_CODES :
																	case JournalEventType::JOURNAL_EVENT_TYPES_REDEEM_POINTS_FOR_INTERNATIONAL_SHOPPING :
																		if ($program->invoice_for_awards) {
																			$this->table [( int ) $program->account_holder_id]->codes_redeemed_premium = $amount;
																		}
																		break;
																	case JournalEventType::JOURNAL_EVENT_TYPES_REDEEM_MONIES_FOR_GIFT_CODES :
																		if (! $program->invoice_for_awards) {
																			$this->table [( int ) $program->account_holder_id]->codes_redeemed_premium = $amount;
																		}
																		break;
																}
																break;
														}
														break;
												}
											}
										}
									}
								}
							}
						}
					}
				}
				// Get the monies awards
				$subreport_params [self::ACCOUNT_TYPES] = [];
				$subreport_params [self::JOURNAL_EVENT_TYPES] = [];

				// $points_report = new ReportServiceSumProgramAwardsMonies ( $subreport_params );
				// $points_report_table = $points_report->getTable ();

                $points_report = $this->reportFactory->build("SumProgramAwardsMonies", $subreport_params);
				$points_report_table = $points_report->getReport ();

				// Sort the points awards
				if (is_array ( $points_report_table ) && count ( $points_report_table ) > 0) {
					foreach ( $points_report_table as $program_account_holder_id => $programs_points_report_table ) {
						// Get an easier reference to the program
						$program = $this->table [$program_account_holder_id];
						if (! $program->invoice_for_awards) {
							$this->table [( int ) $program->account_holder_id]->points_purchased += $programs_points_report_table [AccountType::ACCOUNT_TYPE_MONIES_AWARDED] [JournalEventType::JOURNAL_EVENT_TYPES_AWARD_MONIES_TO_RECIPIENT];
						}
					}
				}
				// Get the points awards
				$subreport_params[self::ACCOUNT_TYPES] = [];
				$subreport_params[self::JOURNAL_EVENT_TYPES] = [];
				// $points_report = new ReportServiceSumProgramAwardsPoints ( $subreport_params );
				// $points_report_table = $points_report->getTable ();
                $points_report = $this->reportFactory->build("SumProgramAwardsPoints", $subreport_params);
				$points_report_table = $points_report->getReport ();
                // dd($points_report_table);
				// Sort the points awards
				if (is_array ( $points_report_table ) && count ( $points_report_table ) > 0) {
					foreach ( $points_report_table as $program_account_holder_id => $programs_points_report_table ) {
						// Get an easier reference to the program
						$program = $this->table [$program_account_holder_id];
						if ($program->invoice_for_awards) {
							$this->table [( int ) $program->account_holder_id]->points_purchased += $programs_points_report_table [AccountType::ACCOUNT_TYPE_POINTS_AWARDED] [JournalEventType::JOURNAL_EVENT_TYPES_AWARD_POINTS_TO_RECIPIENT];
						}
					}
				}

				// Get the points awards
				$subreport_params[self::ACCOUNT_TYPES] = [];
				$subreport_params[self::JOURNAL_EVENT_TYPES] = [];
				// $premium_fee = new ReportServiceSumProgramCostOfGiftCodesRedeemedFee ( $subreport_params );
				// $premium_fee_report_table = $premium_fee->getTable ();
                $premium_fee = $this->reportFactory->build("SumProgramCostOfGiftCodesRedeemedFee", $subreport_params);
				$premium_fee_report_table = $premium_fee->getReport ();

				// pr($premium_fee_report_table);
				//$this->_ci->read_db->query ('INSERT INTO debug SET note = '. json_encode(json_encode($premium_fee_report_table)));
				if (is_array ( $premium_fee_report_table ) && count ( $premium_fee_report_table ) > 0) {
					foreach ( $premium_fee_report_table as $program_account_holder_id => $programs_points_report_table ) {
						// Get an easier reference to the program
						$program = $this->table [$program_account_holder_id];
						if ($program->invoice_for_awards) {
							$this->table [( int ) $program->account_holder_id]->premium_fee += $programs_points_report_table ['Points Redeemed']['Redeem points for gift codes']['premium_fee'];
						}
					}
				}
			}
		}

        //Remove entries with 0 total

        foreach($this->table as $program_account_holder_id => $program) {
            $rowTotal = 0;
            foreach($defaultValues as $key => $value) {
                $rowTotal += $program->{$key};
            }
            if( $rowTotal < 0) {
                unset($this->table[$program_account_holder_id]);
            }
        }

        //Calculate and add "net_points_purchased"
        //$this->table = array_values($this->table);
        foreach( $this->table as $i => $program) {
            $this->table[$i]->net_points_purchased = $this->table[$i]->points_purchased - $this->table[$i]->reclaims - $this->table[$i]->award_credit_reclaims;
        }
		// sort($this->table); //not sure about this whether we need this
	}

	/** Calculate data by date range (timestampFrom|To) */
	protected function getDataDateRange() {
		$this->calcByDateRange ( $this->getParams () );
	}

    public function getCsvHeaders(): array
    {
        return [
            [
                'label' => 'Program',
                'key' => 'name'
            ],
            [
                'label' => 'Setup Fee',
                'key' => 'setup_fee'
            ],
            [
                'label' => 'Fixed Fee',
                'key' => 'fixed_fee'
            ],
            [
                'label' => 'Admin Fee',
                'key' => 'admin_fee'
            ],
            [
                'label' => 'Usage Fee',
                'key' => 'usage_fee'
            ],
            [
                'label' => 'Deposit Fee',
                'key' => 'deposit_fee'
            ],
            [
                'label' => 'Transaction Fees',
                'key' => 'transaction_fee'
            ],
            [
                'label' => 'Refunded Transaction Fees',
                'key' => 'refunded_transaction_fee'
            ],
            [
                'label' => 'Deposits',
                'key' => 'deposits'
            ],
            [
                'label' => 'Points Purchased',
                'key' => 'points_purchased'
            ],
            [
                'label' => 'Reclaims',
                'key' => 'reclaims'
            ],
            [
                'label' => 'Award Credit Reclaimed',
                'key' => 'award_credit_reclaims'
            ],
            [
                'label' => 'Points Redeemed',
                'key' => 'points_redeemed'
            ],
            [
                'label' => 'Discount Rebate Credited to Program',
                'key' => 'discount_rebate_credited_to_program'
            ],
            [
                'label' => 'Total Spend Rebate Credited to Program',
                'key' => 'total_spend_rebate'
            ],
            [
                'label' => 'Expiration Rebate Credited to Program',
                'key' => 'expiration_rebate_credited_to_program'
            ],
            [
                'label' => 'Premium From Codes Redeemed',
                'key' => 'codes_redeemed_premium'
            ],
            [
                'label' => 'Premium Fee',
                'key' => 'premium_fee'
            ],
            [
                'label' => 'Cost of Codes Redeemed',
                'key' => 'codes_redeemed_cost'
            ],
            [
                'label' => 'Convenience Fees',
                'key' => 'convenience_fees'
            ],

        ];
    }
}
