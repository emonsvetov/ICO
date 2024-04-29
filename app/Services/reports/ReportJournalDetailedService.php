<?php
namespace App\Services\reports;

use App\Models\JournalEventType;
use App\Models\AccountType;
use App\Models\Program;

class ReportJournalDetailedService extends ReportServiceAbstract
{
	protected function calc( $params = [] ): array
    {
		// Setup the default params for the sub reports
		$subreport_params = array ();
        $subreport_params [self::DATE_BEGIN] = $this->params [self::DATE_BEGIN];
        $subreport_params [self::DATE_END] = $this->params [self::DATE_END];

        $programAccountHolderIds = $this->params[self::PROGRAMS];
        $newProgramAccountHolderIds = [];
        $programs = Program::whereIn('account_holder_id', $programAccountHolderIds)->get();
        if ($programs) {
            $programIds = $programs->pluck('id')->toArray();
            $topLevelProgramData = $programs[0]->getRoot(['id', 'name']);
            $topLevelProgram = Program::find($topLevelProgramData->id);

            if ($this->params[self::ROOT_ONLY] == 'true'){
                $programs = (new Program)->whereIn('account_holder_id', $programAccountHolderIds)->IsRoot()->get()->toTree();
            } else {
                $programs = (new Program)->whereIn('account_holder_id', $programAccountHolderIds)->get()->toTree();
            }
            $programs = _tree_flatten($programs);

			if ( $programs->isNotEmpty() ) {
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
                    'program_pays_for_saas_fees' => 0,
                    'reversal_program_pays_for_saas_fees' => 0,
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
				];
				foreach ( $programs as $program ) {
					$account_holder_ids[] = $program->account_holder_id;
                    $program->setShownId();
                    $program = (object)$program->toArray();
					$table[$program->account_holder_id] = $program;
                    foreach ($defaultValues as $key => $value) {
                        $table[$program->account_holder_id]->$key = $value;
                    }
				}

				// Get all types of fees, etc where we are interested in them being credits, fees from both award types are the transaction fees,
                // they will be grouped by type, so we can pick which one we want
				$subreport_params [self::ACCOUNT_HOLDER_IDS] = $account_holder_ids;
				$subreport_params [self::PROGRAMS] = $account_holder_ids;
				$subreport_params [ReportSumPostsByAccountAndJournalEventAndCreditService::IS_CREDIT] = 1;
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
					JournalEventType::JOURNAL_EVENT_TYPES_PROMOTIONAL_AWARD,
                    JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_SAAS_FEES,
                    JournalEventType::JOURNAL_EVENT_TYPES_CHARGE_SAAS_FEES,
                    JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_TRANSFERS_MONIES_AVAILABLE
				];
				$credits_report = new ReportSumPostsByAccountAndJournalEventAndCreditService ( $subreport_params );

				$credits_report_table = $credits_report->getTable ();

				if (is_array ( $credits_report_table ) && count ( $credits_report_table ) > 0) {
					foreach ( $credits_report_table as $program_account_holder_id => $programs_credits_report_table ) {
						// Get an easier reference to the program
						$program = $table [$program_account_holder_id];
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
														$table[$program->account_holder_id]->points_purchased = $amount;
														break;
												}
												break;
											case AccountType::ACCOUNT_TYPE_MONIES_FEES :
												switch ($journal_event_type) {
													case JournalEventType::JOURNAL_EVENT_TYPES_AWARD_POINTS_TO_RECIPIENT :
														if ($program->invoice_for_awards) {
															$table[$program->account_holder_id]->transaction_fee = $amount;
														}
														break;
													case JournalEventType::JOURNAL_EVENT_TYPES_AWARD_MONIES_TO_RECIPIENT :
														if (! $program->invoice_for_awards) {
															$table[$program->account_holder_id]->transaction_fee = $amount;
														}
														break;
													case JournalEventType::JOURNAL_EVENT_TYPES_CHARGE_FIXED_FEE :
														$table[$program->account_holder_id]->fixed_fee = $amount;
														break;
													case JournalEventType::JOURNAL_EVENT_TYPES_CHARGE_SETUP_FEE :
														$table[$program->account_holder_id]->setup_fee = $amount;
														break;
													case JournalEventType::JOURNAL_EVENT_TYPES_CHARGE_ADMIN_FEE :
														$table[$program->account_holder_id]->admin_fee = $amount;
														break;
													case JournalEventType::JOURNAL_EVENT_TYPES_CHARGE_MONTHLY_USAGE_FEE :
														$table[$program->account_holder_id]->usage_fee = $amount;
														break;
												}
												break;
											case AccountType::ACCOUNT_TYPE_MONIES_DUE_TO_OWNER :
												switch ($journal_event_type) {
                                                    case JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_SAAS_FEES :
                                                        $table [( int ) $program->account_holder_id]->program_pays_for_saas_fees = $amount;
                                                        break;
                                                    case JournalEventType::JOURNAL_EVENT_TYPES_CHARGE_SAAS_FEES :
                                                        $table [( int ) $program->account_holder_id]->charge_program_for_saas_fees = $amount;
                                                        break;
													case JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_DEPOSIT_FEE :
														$table[$program->account_holder_id]->deposit_fee = $amount;
														break;
													case JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_CONVENIENCE_FEE :
														$table[$program->account_holder_id]->convenience_fees = $amount;
														break;
													case JournalEventType::JOURNAL_EVENT_TYPES_REFUND_PROGRAM_FOR_POINTS_TRANSACTION_FEE :
														$table[$program->account_holder_id]->refunded_transaction_fee = $amount;
														break;
													case JournalEventType::JOURNAL_EVENT_TYPES_RECLAIM_POINTS :
														$table[$program->account_holder_id]->reclaims = $amount;
														break;
                                                    case JournalEventType::JOURNAL_EVENT_TYPES_AWARD_CREDIT_RECLAIM_POINTS :
                                                        $table [$program->account_holder_id]->award_credit_reclaims = $amount;
                                                        break;
												}
												break;
											case AccountType::ACCOUNT_TYPE_MONIES_AVAILABLE :
												switch ($journal_event_type) {
													case JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_MONIES_PENDING :
														$table[$program->account_holder_id]->deposits = $amount;
														break;
													case JournalEventType::JOURNAL_EVENT_TYPES_RECLAIM_MONIES :
														$table[$program->account_holder_id]->reclaims = $amount;
														break;
													case JournalEventType::JOURNAL_EVENT_TYPES_REFUND_PROGRAM_FOR_MONIES_TRANSACTION_FEE :
														$table[$program->account_holder_id]->refunded_transaction_fee = $amount;
														break;
												}
                                                if ($journal_event_type == JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_TRANSFERS_MONIES_AVAILABLE) {
                                                    $table[$program->account_holder_id]->program_funds_net_transfers = $amount;
                                                }
												break;
											case AccountType::ACCOUNT_TYPE_MONIES_SHARED :
												switch ($journal_event_type) {
													case JournalEventType::JOURNAL_EVENT_TYPES_REDEEM_POINTS_FOR_GIFT_CODES :
													case JournalEventType::JOURNAL_EVENT_TYPES_REDEEM_POINTS_FOR_INTERNATIONAL_SHOPPING :
														if ($program->invoice_for_awards) {
															$table[$program->account_holder_id]->discount_rebate_credited_to_program = $amount;
														}
														break;
													case JournalEventType::JOURNAL_EVENT_TYPES_REDEEM_MONIES_FOR_GIFT_CODES :
														if (! $program->invoice_for_awards) {
															$table[$program->account_holder_id]->discount_rebate_credited_to_program = $amount;
														}
														break;
													case JournalEventType::JOURNAL_EVENT_TYPES_EXPIRE_POINTS :
														if ($program->invoice_for_awards) {
															$table[$program->account_holder_id]->expiration_rebate_credited_to_program += $amount; // Add so expire and deactive are summed
														}
														break;
													case JournalEventType::JOURNAL_EVENT_TYPES_EXPIRE_MONIES :
														if (! $program->invoice_for_awards) {
															$table[$program->account_holder_id]->expiration_rebate_credited_to_program += $amount; // Add so expire and deactive are summed
														}
														break;
													case JournalEventType::JOURNAL_EVENT_TYPES_DEACTIVATE_POINTS :
														if ($program->invoice_for_awards) {
															$table[$program->account_holder_id]->expiration_rebate_credited_to_program += $amount; // Add so expire and deactive are summed
														}
														break;
													case JournalEventType::JOURNAL_EVENT_TYPES_DEACTIVATE_MONIES :
														if (! $program->invoice_for_awards) {
															$table[$program->account_holder_id]->expiration_rebate_credited_to_program += $amount; // Add so expire and deactive are summed
														}
														break;
													case JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_TOTAL_SPEND_REBATE :
														$table[$program->account_holder_id]->total_spend_rebate = $amount;
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
				$subreport_params[ReportSumPostsByAccountAndJournalEventAndCreditService::IS_CREDIT] = 0;
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
				$debits_report = new ReportSumPostsByAccountAndJournalEventAndCreditService ( $subreport_params );
				$debits_report_table = $debits_report->getTable ();

				// Sort the second set of fees
				if (is_array ( $debits_report_table ) && count ( $debits_report_table ) > 0) {
					foreach ( $debits_report_table as $program_account_holder_id => $programs_debits_report_table ) {
						// Get an easier reference to the program
						$program = $table [$program_account_holder_id];
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
															$table [( int ) $program->account_holder_id]->points_redeemed = $amount;
														}
													break;
													case JournalEventType::JOURNAL_EVENT_TYPES_REDEEM_MONIES_FOR_GIFT_CODES :
														if (! $program->invoice_for_awards) {
															$table [( int ) $program->account_holder_id]->points_redeemed = $amount;
														}
													break;
												}
												break;
											case AccountType::ACCOUNT_TYPE_MONIES_AVAILABLE :
												switch ($journal_event_type) {
													case JournalEventType::JOURNAL_EVENT_TYPES_REVERSAL_PROGRAM_PAYS_FOR_MONIES_PENDING :
														if (! $program->invoice_for_awards) {
															$table [( int ) $program->account_holder_id]->deposits -= $amount;
														}
													break;
												}
												break;
											case AccountType::ACCOUNT_TYPE_MONIES_DUE_TO_OWNER :
												switch ($journal_event_type) {
													case JournalEventType::JOURNAL_EVENT_TYPES_REVERSAL_PROGRAM_PAYS_FOR_POINTS :
														if ($program->invoice_for_awards) {
															// Not sure if we need this as we aren't including payments that were made..
															// $table[(int)$program->account_holder_id]->points_purchased -= $amount;
														}
                                                        case JournalEventType::JOURNAL_EVENT_TYPES_REVERSAL_PROGRAM_PAYS_FOR_SETUP_FEE:
                                                            $table[(int)$program->account_holder_id]->setup_fee -= $amount;
                                                            break;
                                                        case JournalEventType::JOURNAL_EVENT_TYPES_REVERSAL_PROGRAM_PAYS_FOR_FIXED_FEE:
                                                            $table[(int)$program->account_holder_id]->program_fixed_fee -= $amount;
    //                                                        $table[(int)$program->account_holder_id]->fixed_fee_reversal = $amount;
                                                            break;
                                                        case JournalEventType::JOURNAL_EVENT_TYPES_REVERSAL_PROGRAM_PAYS_FOR_MONTHLY_USAGE_FEE:
                                                            $table[(int)$program->account_holder_id]->usage_fee -= $amount;
                                                            break;
                                                        case JournalEventType::JOURNAL_EVENT_TYPES_REVERSAL_PROGRAM_PAYS_FOR_DEPOSIT_FEE:
                                                            $table[(int)$program->account_holder_id]->deposit_fee -= $amount;
    //                                                        $table[(int)$program->account_holder_id]->deposit_fee_reversal = $amount;
                                                            break;
                                                        case JournalEventType::JOURNAL_EVENT_TYPES_REVERSAL_PROGRAM_PAYS_FOR_CONVENIENCE_FEE:
                                                            $table[(int)$program->account_holder_id]->convenience_fees -= $amount;
    //
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
				$cost_of_redeemed_report = new ReportSumProgramCostOfGiftCodesRedeemedService ( $subreport_params );
				$cost_of_redeemed_report_table = $cost_of_redeemed_report->getTable ();
				if (is_array ( $cost_of_redeemed_report_table ) && count ( $cost_of_redeemed_report_table ) > 0) {
					foreach ( $cost_of_redeemed_report_table as $program_account_holder_id => $programs_cost_of_redeemed_report_table ) {
						// Get an easier reference to the program
						$program = $table [$program_account_holder_id];
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
																			$table [( int ) $program->account_holder_id]->codes_redeemed_cost = $amount;
																		}
																		break;
																	case JournalEventType::JOURNAL_EVENT_TYPES_REDEEM_MONIES_FOR_GIFT_CODES :
																		if (! $program->invoice_for_awards) {
																			$table [( int ) $program->account_holder_id]->codes_redeemed_cost = $amount;
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
																			$table [( int ) $program->account_holder_id]->codes_redeemed_premium = $amount;
																		}
																		break;
																	case JournalEventType::JOURNAL_EVENT_TYPES_REDEEM_MONIES_FOR_GIFT_CODES :
																		if (! $program->invoice_for_awards) {
																			$table [( int ) $program->account_holder_id]->codes_redeemed_premium = $amount;
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

				$points_report = new ReportSumProgramAwardsMoniesService ( $subreport_params );

				$points_report_table = $points_report->getTable ();

				// Sort the points awards
				if (is_array ( $points_report_table ) && count ( $points_report_table ) > 0) {
					foreach ( $points_report_table as $program_account_holder_id => $programs_points_report_table ) {
						// Get an easier reference to the program
						$program = $table [$program_account_holder_id];
						if (! $program->invoice_for_awards) {
							$table [( int ) $program->account_holder_id]->points_purchased += $programs_points_report_table [AccountType::ACCOUNT_TYPE_MONIES_AWARDED] [JournalEventType::JOURNAL_EVENT_TYPES_AWARD_MONIES_TO_RECIPIENT];
						}
					}
				}
				// Get the points awards
				$subreport_params[self::ACCOUNT_TYPES] = [];
				$subreport_params[self::JOURNAL_EVENT_TYPES] = [];
				$points_report = new ReportSumProgramAwardsPointsService ( $subreport_params );
				$points_report_table = $points_report->getTable ();
				// Sort the points awards
				if (is_array ( $points_report_table ) && count ( $points_report_table ) > 0) {
					foreach ( $points_report_table as $program_account_holder_id => $programs_points_report_table ) {
						// Get an easier reference to the program
						$program = $table [$program_account_holder_id];
						if ($program->invoice_for_awards) {
							$table [( int ) $program->account_holder_id]->points_purchased += $programs_points_report_table [AccountType::ACCOUNT_TYPE_POINTS_AWARDED] [JournalEventType::JOURNAL_EVENT_TYPES_AWARD_POINTS_TO_RECIPIENT];
						}
					}
				}

				// Get the points awards @TODO: V2 is broken
				$subreport_params[self::ACCOUNT_TYPES] = [AccountType::ACCOUNT_TYPE_POINTS_REDEEMED];
				$subreport_params[self::JOURNAL_EVENT_TYPES] = [JournalEventType::JOURNAL_EVENT_TYPES_REDEEM_POINTS_FOR_GIFT_CODES];
				$premium_fee = new ReportSumProgramCostOfGiftCodesRedeemedFeeService ( $subreport_params );
				$premium_fee_report_table = $premium_fee->getTable ();



				if (is_array ( $premium_fee_report_table ) && count ( $premium_fee_report_table ) > 0) {
					foreach ( $premium_fee_report_table as $program_account_holder_id => $programs_points_report_table ) {
						// Get an easier reference to the program
						$program = $table [$program_account_holder_id];
						if ($program->invoice_for_awards) {
							$table [( int ) $program->account_holder_id]->premium_fee += $programs_points_report_table ['Points Redeemed']['Redeem points for gift codes']['premium_fee'];
						}
					}
				}
			}
		}

        //Remove entries with 0 total
        foreach($table as $program_account_holder_id => $program) {
            $rowTotal = 0;
            foreach($defaultValues as $key => $value) {
                $rowTotal += $program->{$key};
            }
            if( $rowTotal < 0) {
                unset($table[$program_account_holder_id]);
            }
        }

        //Calculate and add "net_points_purchased"
        foreach( $table as $i => $program) {
            $table[$i]->net_points_purchased = $table[$i]->points_purchased - $table[$i]->reclaims - $table[$i]->award_credit_reclaims;
        }
		$temp = array();
		foreach( $table as $i => $program) {
			array_push($temp, $program);
		}
		$table = $temp;
		sort($table);
        $newTable = [];

        if ($this->params[self::FLAT_DATA]){
            $this->table = [];
            $this->table['data'] = array_values($table);
            $this->table['total'] = count($table);

            return $this->table;
        }

        foreach ($table as $key => $item) {
            if ($item->parent_id == $programs[0]->parent_id) {
                $newTable[$item->id] = clone $item;

                foreach ($defaultValues as $key => $value) {
                    $newTable[$item->id]->$key = $this->amountFormat($item->$key);
                }
            } else {
                $tmpPath = explode(',', $item->dinamicPath);
                $tmpPath = array_diff($tmpPath, explode(',',$programs[0]->dinamicPath));
                $first = reset($tmpPath);

                if (isset($newTable[$first])) {
                    $newTable = $this->tableToTree($newTable, $item, $tmpPath, 0, $defaultValues);

                    $firstChild = $newTable[$first]->subRows[0] ?? null;
                    if ($firstChild && $firstChild->id == $first){
                    } else {
                        $tmpEl = clone $newTable[$first];
                        $tmpEl->dinamicDepth = 0;
                        unset($tmpEl->subRows);
                        array_unshift($newTable[$first]->subRows, $tmpEl);
                    }

                    foreach ($defaultValues as $key => $value) {
                        $newTable[$first]->$key = $this->amountFormat($newTable[$first]->$key + $this->amountFormat($item->$key));
                    }
                }
            }
        }
        $this->table = [];
        $this->table['data'] = array_values($newTable);
        $this->table['total'] = count($newTable);
        return $this->table;
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
                'label' => 'Program Account Holder ID',
                'key' => 'account_holder_id'
            ],
            [
                'label' => 'Deposits',
                'key' => 'deposits'
            ],
            [
                'label' => 'Deposit Reversal',
                'key' => 'deposit_reversal'
            ],
            [
                'label' => 'Program Funds net transfers',
                'key' => 'program_funds_net_transfers'
            ],
            [
                'label' => 'Points Purchased',
                'key' => 'points_purchased'
            ],
            [
                'label' => 'Points Reclaimed',
                'key' => 'reclaims'
            ],
            [
                'label' => 'Award Credit Reclaimed',
                'key' => 'award_credit_reclaims'
            ],
            [
                'label' => 'Net Points purchased',
                'key' => 'net_points_purchased'
            ],
            [
                'label' => 'Deposit Fee',
                'key' => 'deposit_fee'
            ],
            [
                'label' => 'Fixed Fee',
                'key' => 'fixed_fee'
            ],
            [
                'label' => 'Usage Fee',
                'key' => 'usage_fee'
            ],
            [
                'label' => 'Convenience Fees',
                'key' => 'convenience_fees'
            ],
            [
                'label' => 'Premium Fee billed To client',
                'key' => 'premium_fee'
            ],
            [
                'label' => 'License Fee',
                'key' => 'program_pays_for_saas_fees'
            ],
            [
                'label' => 'Setup Fee',
                'key' => 'setup_fee'
            ],
            [
                'label' => 'Points Redeemed',
                'key' => 'points_redeemed'
            ],
            [
                'label' => 'Premium From Codes Redeemed',
                'key' => 'codes_redeemed_premium'
            ],
            [
                'label' => 'Cost of Codes Redeemed',
                'key' => 'codes_redeemed_cost'
            ],
            [
                'label' => 'Program refunds for monies pending',
                'key' => 'program_refunds_for_monies_pending'
            ],
            [
                'label' => 'Total Spend Rebate',
                'key' => 'total_spend_rebate'
            ],
            [
                'label' => 'Discount Rebate',
                'key' => 'discount_rebate_credited_to_program'
            ],
            [
                'label' => 'Expiration Rebate',
                'key' => 'expiration_rebate_credited_to_program'
            ]
        ];
    }
    protected function getReportForCSV(): array
    {
        $this->isExport = true;
        $table = $this->getTable();
        $data['headers'] = $this->getCsvHeaders();

        // prepare data
        $exists = [];
        foreach ( $table as $row ) {
            foreach ($data['headers'] as $header) {
                if (isset($row[$header['key']]) && !in_array($header['key'], ['name', 'account_holder_id'])) {
                    $row[$header['key']] = number_format($row[$header['key']], 2, '.', '');
                }
            }
        }

        $temp = [];
        foreach ($table['data'] as $key => $item) {
            array_push($temp, $item);
            $exists[] = $item->account_holder_id;
            if (isset($item->subRows)) {
                $temp = $this->addChild($exists, $temp, $item);
            }
        }
        $data['data'] = $temp;

        return $data;
    }

    public function addChild($exists, $temp, $item, $level = 1){
        $level++;
        foreach($item->subRows as $sub => $subItem) {
            if (in_array($subItem->account_holder_id, $exists)) {
                continue;
            }
            $subItem->name = str_repeat(' • ', $level-1) . $subItem->name;
            array_push($temp, $subItem);
            if (isset($subItem->subRows)) {
                $temp = $this->addChild($exists, $temp, $subItem, $level);
            }
        }
        return $temp;
    }
}
