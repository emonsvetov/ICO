<?php
namespace App\Services\Report;

use App\Services\Report\ReportServiceSumPostsByAccountAndJournalEventAndCredit;
use App\Services\Report\ReportServiceSumProgramCostOfGiftCodesRedeemed;
use App\Services\Report\ReportServiceAbstractBase;
use App\Models\JournalEventType;
use App\Models\AccountType;
use App\Models\Program;

class ReportServiceJournalDetail extends ReportServiceAbstractBase
{
	protected function calcByDateRange(Array $params) {
		$this->table = array ();
		// Setup the default params for the sub reports
		$subreport_params = array ();
		$subreport_params [self::DATE_BEGIN] = $params [self::DATE_BEGIN];
		$subreport_params [self::DATE_END] = $params [self::DATE_END];

		if (is_array ( $this->params[self::PROGRAMS] ) && count ( $this->params[self::PROGRAMS] ) > 0) {
			$ranked_programs = Program::read_programs ( $this->params [self::PROGRAMS], true );
			if ( $ranked_programs->isNotEmpty() ) {
				$account_holder_ids = [];
				$defaultValues = [
					'program_fixed_fee' => 0,
					'program_setup_fee' => 0,
					'admin_fee' => 0,
					'usage_fee' => 0,
					'deposit_fee' => 0,
					'transaction_fees' => 0,
					'refunded_transaction_fees' => 0,
					'deposits' => 0,
					'points_purchased' => 0,
					'points_redeemed' => 0,
					'reclaims' => 0,
					'discount_rebate_credited_to_program' => 0,
					'total_spend_rebate' => 0,
					'expiration_rebate_credited_to_program' => 0,
					'codes_redeemed_cost' => 0,
					'codes_redeemed_premium' => 0,
					'convenience_fees' => 0,
					'premium_fee' => 0
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
				$subreport_params [ReportServiceSumPostsByAccountAndJournalEventAndCredit::IS_CREDIT] = 1;
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
					JournalEventType::JOURNAL_EVENT_TYPES_RECLAIM_MONIES,
					JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_TOTAL_SPEND_REBATE,
					JournalEventType::JOURNAL_EVENT_TYPES_REDEEMABLE_ON_INTERNAL_STORE,
					JournalEventType::JOURNAL_EVENT_TYPES_PROMOTIONAL_AWARD
				];
				$credits_report = new ReportServiceSumPostsByAccountAndJournalEventAndCredit ( $subreport_params );
				$credits_report_table = $credits_report->getTable ();
				// pr($credits_report_table);
				// exit;
				if (is_array ( $credits_report_table ) && count ( $credits_report_table ) > 0) {
					foreach ( $credits_report_table as $program_account_holder_id => $programs_credits_report_table ) {
						// Get an easier reference to the program
						$program = $this->table [$program_account_holder_id];
						if (is_array ( $programs_credits_report_table ) && count ( $programs_credits_report_table ) > 0) {
							foreach ( $programs_credits_report_table as $account_type_name => $account ) {
								if (is_array ( $account ) && count ( $account ) > 0) {
									foreach ( $account as $journal_event_type => $amount ) {
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
															$this->table[$program->account_holder_id]->transaction_fees = $amount;
														}
														break;
													case JournalEventType::JOURNAL_EVENT_TYPES_AWARD_MONIES_TO_RECIPIENT :
														if (! $program->invoice_for_awards) {
															$this->table[$program->account_holder_id]->transaction_fees = $amount;
														}
														break;
													case JournalEventType::JOURNAL_EVENT_TYPES_CHARGE_FIXED_FEE :
														$this->table[$program->account_holder_id]->program_fixed_fee = $amount;
														break;
													case JournalEventType::JOURNAL_EVENT_TYPES_CHARGE_SETUP_FEE :
														$this->table[$program->account_holder_id]->program_setup_fee = $amount;
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
														$this->table[$program->account_holder_id]->refunded_transaction_fees = $amount;
														break;
													case JournalEventType::JOURNAL_EVENT_TYPES_RECLAIM_POINTS :
														$this->table[$program->account_holder_id]->reclaims = $amount;
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
														$this->table[$program->account_holder_id]->refunded_transaction_fees = $amount;
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
				$subreport_params[ReportServiceSumPostsByAccountAndJournalEventAndCredit::IS_CREDIT] = 0;
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
					JournalEventType::JOURNAL_EVENT_TYPES_REVERSAL_PROGRAM_PAYS_FOR_MONIES_PENDING 
				);
				$debits_report = new ReportServiceSumPostsByAccountAndJournalEventAndCredit ( $subreport_params );
				$debits_report_table = $debits_report->getTable ();
				// Sort the second set of fees
				if (is_array ( $debits_report_table ) && count ( $debits_report_table ) > 0) {
					foreach ( $debits_report_table as $program_account_holder_id => $programs_debits_report_table ) {
						// Get an easier reference to the program
						$program = $this->table [$program_account_holder_id];
						if (is_array ( $programs_debits_report_table ) && count ( $programs_debits_report_table ) > 0) {
							foreach ( $programs_debits_report_table as $account_type_name => $account ) {
								if (is_array ( $account ) && count ( $account ) > 0) {
									foreach ( $account as $journal_event_type => $amount ) {
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
											case ACCOUNT_TYPE_MONIES_DUE_TO_OWNER :
												switch ($journal_event_type) {
													case JournalEventType::JOURNAL_EVENT_TYPES_REVERSAL_PROGRAM_PAYS_FOR_POINTS :
														if ($program->invoice_for_awards) {
															// Not sure if we need this as we aren't including payments that were made..
															// $this->table[(int)$program->account_holder_id]->points_purchased -= $amount;
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
				// Get the cost of gift codes redeemed
				$subreport_params[self::JOURNAL_EVENT_TYPES] = array (
					JournalEventType::JOURNAL_EVENT_TYPES_REDEEM_POINTS_FOR_GIFT_CODES,
					JournalEventType::JOURNAL_EVENT_TYPES_REDEEM_POINTS_FOR_INTERNATIONAL_SHOPPING,
					JournalEventType::JOURNAL_EVENT_TYPES_REDEEM_MONIES_FOR_GIFT_CODES 
				);
				$cost_of_redeemed_report = new ReportServiceSumProgramCostOfGiftCodesRedeemed ( $subreport_params );
				$cost_of_redeemed_report_table = $cost_of_redeemed_report->getTable ();
				pr($cost_of_redeemed_report_table);
			}
		}
	}

	protected function getDataDateRange() {
		$this->calcByDateRange ( $this->getParams () );
	}
}
