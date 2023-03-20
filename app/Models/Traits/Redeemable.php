<?php
namespace App\Models\Traits;

use Illuminate\Support\Facades\DB;

use App\Models\JournalEventType;
use App\Models\JournalEvent;
use App\Models\FinanceType;
use App\Models\MediumType;
use App\Models\Merchant;
use App\Models\Account;
use App\Models\Posting;

trait Redeemable
{
	private static function _read_redeemable_list_by_merchant( $merchant, $filters = [] )	{

		if( !is_object($merchant) && is_numeric($merchant) )	{
			$merchant = Merchant::find($merchant);
		}

		DB::statement("SET SQL_MODE=''"); //SQLSTATE[42000] fix!
		$query = self::selectRaw(
			"'{$merchant->id}' as merchant_id,
			'{$merchant->account_holder_id}' as merchant_account_holder_id,
			`redemption_value`,
			`sku_value`,
			`redemption_value` - `sku_value` as `redemption_fee`,
			COUNT(DISTINCT medium_info.`id`) as count"
		)
		->join('postings', 'postings.medium_info_id', '=', 'medium_info.id')
		->join('accounts AS a', 'postings.account_id', '=', 'a.id')
		->groupBy('sku_value')
		->groupBy('redemption_value')
		->orderBy('sku_value')
		->orderBy('redemption_value')
		->where('a.account_holder_id', $merchant->account_holder_id)
		->where('medium_info.hold_until', '<=', now())
        ;

		if( !empty($filters['redemption_value']) )	{
			$query = $query->where('redemption_value', '=', $filters['redemption_value']);
		}

		if( !empty($filters['sku_value']) )	{
			$query = $query->where('sku_value', '=', $filters['sku_value']);
		}

		if( !empty($filters['end_date']) && isValidDate($filters['end_date']) )	{
			$query = $query->where('purchase_date', '<=', $filters['end_date']);
			$query = $query->where(function($query1) use($filters) {
                $query1->orWhere('redemption_date', null)
                ->orWhere('redemption_date', '>', $filters['end_date']);
            });
		}

		return $query->get();
	}

    private static function _redeem_points_for_giftcodes_no_transaction( $params )    {

		extract($params);

		// Note: $code is a "Posting" Model object having "id" as medium_info.id

		$response = [];

		try {

			$journal_event_type_id = JournalEventType::getIdByType( "Redeem points for gift codes" );

			$journal_event_id = JournalEvent::insertGetId([
				'created_at' => now(),
				'journal_event_type_id' => $journal_event_type_id,
				'prime_account_holder_id' => $user->account_holder_id,
			]);

			//Set FinanceTypes
			$finance_type_asset_id = FinanceType::getIdByName('Asset', true);
			if( !$finance_type_asset_id ) return ['errors' => sprintf('Finance type Asset could not be set')];

			$finance_type_liability_id = FinanceType::getIdByName('Liability', true);
			if( !$finance_type_liability_id ) return ['errors' => sprintf('Finance type Liability could not be set')];

			$finance_type_revenue_id = FinanceType::getIdByName('Revenue', true);
			if( !$finance_type_revenue_id ) return ['errors' => sprintf('Finance type Revenue could not be set')];

			//Set MediumTypes

			$medium_type_giftcodes_id = MediumType::getIdByName('Gift Codes', true);
			if( !$medium_type_giftcodes_id ) return ['errors' => sprintf('MediumType Gift Codes could not be set')];

			$medium_type_points_id = MediumType::getIdByName('Points', true);
			if( !$medium_type_points_id ) return ['errors' => sprintf('MediumType Points could not be set')];

			$medium_type_monies_id = MediumType::getIdByName('Monies', true);
			if( !$medium_type_monies_id ) return ['errors' => sprintf('MediumType Monies could not be set')];

			$response['postings']['redeemed']['user'] = Account::postings(
				$user->account_holder_id,
				'Gift Codes Redeemed',
				$finance_type_asset_id,
				$medium_type_giftcodes_id,
				$code->merchant->account_holder_id,
				'Gift Codes Available',
				$finance_type_asset_id,
				$medium_type_giftcodes_id,
				$journal_event_id,
				$code->cost_basis,
				1, //qty
				null, // medium_info
				$code->id, // medium_info_id
				$currency_id
			);

			$response['postings']['awarded'] = Account::postings(
				$user->account_holder_id,
				'Points Awarded',
				$finance_type_liability_id,
				$medium_type_points_id,
				$program->account_holder_id,
				'Points Redeemed',
				$finance_type_liability_id,
				$medium_type_points_id,
				$journal_event_id,
				$points_to_redeem,
				1, //qty
				null, // medium_info
				null, // medium_info_id
				$currency_id
			);

			$response['postings']['redeemed']['program'] = Account::postings(
				$program->account_holder_id,
				'Points Redeemed',
				$finance_type_liability_id,
				$medium_type_points_id,
				$owner_id,
				'Income',
				$finance_type_revenue_id,
				$medium_type_monies_id,
				$journal_event_id,
				$points_to_redeem,
				1, //qty
				null, // medium_info
				null, // medium_info_id
				$currency_id
			);

			$response['postings']['redeemed']['owner'] = Account::postings(
				$owner_id,
				'Income',
				$finance_type_revenue_id,
				$medium_type_monies_id,
				$user->account_holder_id,
				'Gift Codes Redeemed',
				$finance_type_asset_id,
				$medium_type_giftcodes_id,
				$journal_event_id,
				$code->cost_basis,
				1, //qty
				null, // medium_info
				$code->id, // medium_info_id
				$currency_id
			);

			$program_revenue_share = 0;
			if( !empty($program->discount_rebate_percentage) && $program->discount_rebate_percentage > 0 )	{ //TODO- Need to create this field
				$program_revenue_share = $program->discount_rebate_percentage;
			}

			$income_value = round(($code->discount * ($program_revenue_share / 100)),2);

			$response['postings']['redeemed']['discount'] = Account::postings(
				$owner_id,
				'Income',
				$finance_type_revenue_id,
				$medium_type_monies_id,
				$program->account_holder_id,
				'Monies Shared',
				$finance_type_liability_id,
				$medium_type_monies_id,
				$journal_event_id,
				$income_value,
				1, //qty
				null, // medium_info
				$code->id, // medium_info_id
				$currency_id
			);

			if( $code->id > 0 )	{ //which it should!
				$medium_info = self::find( $code->id );
				$medium_info->update(
				[
					'redemption_date' => now(),
					'redemption_datetime' => now(),
					'redeemed_user_id' => $user->id,
					'redeemed_merchant_id' => $code->merchant->id,
					'redeemed_program_id' => $program->id,
				]);
				$response['success'] = true;
				$response['journal_event_id'] = $journal_event_id;
				$response['medium_info_id'] = $code->id;
			}
		} catch (Exception $e)	{
			$response['errors'] = "Error while redeeming. Message: {$e->getMessage()} in line: {$e->getLine()}";
		}

		return $response;
    }

    private static function _redeem_monies_for_giftcodes_no_transaction( $params )    {

		extract($params);

		// Note: $code is a "Posting" Model object having "id" as medium_info.id

		$response = [];

		try {

			$journal_event_type_id = JournalEventType::getIdByType( "Redeem monies for gift codes" );

			$journal_event_id = JournalEvent::insertGetId([
				'created_at' => now(),
				'journal_event_type_id' => $journal_event_type_id,
				'prime_account_holder_id' => $user->account_holder_id,
			]);

			//Set FinanceTypes
			$finance_type_asset_id = FinanceType::getIdByName('Asset', true);
			if( !$finance_type_asset_id ) return ['errors' => sprintf('Finance type Asset could not be set')];

			$finance_type_liability_id = FinanceType::getIdByName('Liability', true);
			if( !$finance_type_liability_id ) return ['errors' => sprintf('Finance type Liability could not be set')];

			$finance_type_revenue_id = FinanceType::getIdByName('Revenue', true);
			if( !$finance_type_revenue_id ) return ['errors' => sprintf('Finance type Revenue could not be set')];

			//Set MediumTypes

			$medium_type_giftcodes_id = MediumType::getIdByName('Gift Codes', true);
			if( !$medium_type_giftcodes_id ) return ['errors' => sprintf('MediumType Gift Codes could not be set')];

			$medium_type_points_id = MediumType::getIdByName('Points', true);
			if( !$medium_type_points_id ) return ['errors' => sprintf('MediumType Points could not be set')];

			$medium_type_monies_id = MediumType::getIdByName('Monies', true);
			if( !$medium_type_monies_id ) return ['errors' => sprintf('MediumType Monies could not be set')];

			$response['postings']['redeemed']['user'] = Account::postings(
				$user->account_holder_id,
				'Gift Codes Redeemed',
				$finance_type_asset_id,
				$medium_type_giftcodes_id,
				$code->merchant->account_holder_id,
				'Gift Codes Available',
				$finance_type_asset_id,
				$medium_type_giftcodes_id,
				$journal_event_id,
				$code->cost_basis,
				1, //qty
				null, // medium_info
				$code->id, // medium_info_id
				$currency_id
			);

			$response['postings']['awarded'] = Account::postings(
				$user->account_holder_id,
				'Monies Awarded',
				$finance_type_liability_id,
				$medium_type_monies_id,
				$program->account_holder_id,
				'Monies Redeemed',
				$finance_type_liability_id,
				$medium_type_monies_id,
				$journal_event_id,
				$points_to_redeem,
				1, //qty
				null, // medium_info
				null, // medium_info_id
				$currency_id
			);

			$response['postings']['redeemed']['program'] = Account::postings(
				$program->account_holder_id,
				'Monies Redeemed',
				$finance_type_liability_id,
				$medium_type_monies_id,
				$owner_id,
				'Income',
				$finance_type_revenue_id,
				$medium_type_monies_id,
				$journal_event_id,
				$points_to_redeem,
				1, //qty
				null, // medium_info
				null, // medium_info_id
				$currency_id
			);

			$response['postings']['redeemed']['owner'] = Account::postings(
				$owner_id,
				'Income',
				$finance_type_revenue_id,
				$medium_type_monies_id,
				$user->account_holder_id,
				'Gift Codes Redeemed',
				$finance_type_asset_id,
				$medium_type_giftcodes_id,
				$journal_event_id,
				$code->cost_basis,
				1, //qty
				null, // medium_info
				$code->id, // medium_info_id
				$currency_id
			);

			$program_revenue_share = 0;
			if( !empty($program->discount_rebate_percentage) && $program->discount_rebate_percentage > 0 )	{ //TODO- Need to create this field
				$program_revenue_share = $program->discount_rebate_percentage;
			}

			$income_value = round(($code->discount * ($program_revenue_share / 100)),2);

			$response['postings']['redeemed']['discount'] = Account::postings(
				$owner_id,
				'Income',
				$finance_type_revenue_id,
				$medium_type_monies_id,
				$program->account_holder_id,
				'Monies Shared',
				$finance_type_liability_id,
				$medium_type_monies_id,
				$journal_event_id,
				$income_value,
				1, //qty
				null, // medium_info
				$code->id, // medium_info_id
				$currency_id
			);

			if( $code->id > 0 )	{ //which it should!
				$medium_info = self::find( $code->id );
				$medium_info->update(
				[
					'redemption_date' => now(),
					'redemption_datetime' => now(),
					'redeemed_user_id' => $user->id,
					'redeemed_merchant_id' => $code->merchant->id,
					'redeemed_program_id' => $program->id,
				]);
				$response['success'] = true;
				$response['journal_event_id'] = $journal_event_id;
				$response['medium_info_id'] = $code->id;
			}
		} catch (Exception $e)	{
			$response['errors'] = "Error while redeeming. Message: {$e->getMessage()} in line: {$e->getLine()}";
		}

		return $response;
    }

    public static function _handle_premium_diff( $params )  {
        extract($params);
        // Determine the premium difference
        $premiumDiff = $code->redemption_value - $code->sku_value;
        //$skuValue = number_format ( ( float ) $code->sku_value, 4, '.', '' );
        //$premiumDiff = ($points_to_redeem - $skuValue);
        $premiumDiff = number_format ( ( float ) $premiumDiff, 4, '.', '' );
        // pr($premiumDiff);
        $sql = "
        SELECT
        je.prime_account_holder_id,
        p.account_id,
        p.posting_amount AS amount,
        p.qty as qty,
        a.account_type_id
        FROM journal_events AS je
        INNER JOIN postings AS p ON (p.journal_event_id = je.id)
        INNER JOIN journal_event_types AS jet ON (jet.id = je.journal_event_type_id)
        INNER JOIN accounts AS a ON(a.id = p.account_id)
        INNER JOIN account_types AS atn ON(atn.id = a.account_type_id)
        WHERE
        je.id =" . $journal_event_id;

        $result = DB::select($sql);
        // get the count for the results
        $num_rows = count($result);
        // pr($num_rows);
        $account_type_id = 0;
        // loop the results
        for($i = 0; $i < $num_rows; $i ++) {
            $a = ( int ) $result [$i]->prime_account_holder_id . "---" . $result[$i]->amount;
            // pr($a);
            if ($result[$i]->account_type_id == 14  || $result[$i]->account_type_id == 19) {
                // This is program
                $account_type_id = $result[$i]->account_type_id;
                $accountIdProgram = $result[$i]->account_id;
            }
            if ($result[$i]->account_type_id == 9 || $result[$i]->account_type_id == 16) {
                // This is program
                $account_type_id = $result[$i]->account_type_id;
                $accountIdUser = $result[$i]->account_id;
            }
        }
        if ($premiumDiff) {

            Posting::create([
                'journal_event_id' => $journal_event_id,
                'posting_amount' => $premiumDiff,
                'is_credit' => 0,
                'qty' => 1,
                'medium_info_id' => 0,
                'created_at' => now(),
                'account_id' => $accountIdProgram
            ]);

            if ($account_type_id != 16) {
                /*$sql = "INSERT INTO postings SET
                            journal_event_id = {$journal_event_id},
                            medium_info_id = 0,
                            account_id = {$accountIdUser},
                            posting_amount = {$premiumDiff},
                            qty = 1,
                            is_credit = 1";
                //	$this->write_db->query ( $sql );
                try {
                    DB::statement($sql);
                }	catch (RuntimeException $e) {
                    throw new RuntimeException ( "SQL query failed, query: {$sql}. Error:" . $e->getMessage(), 500 );
                }	*/
            }
        }
    }

    private static function _transfer_giftcodes_to_merchant_no_transaction( $data )   {

            extract($data); // extracts $user, $code and $currency_id
            // Note: $code is a "Posting" Model object having "id" as medium_info.id
            $response = [];

            try {

                $journal_event_type_id = JournalEventType::getIdByType( "Transfer gift codes" );

                $journal_event_id = JournalEvent::insertGetId([
                    'created_at' => now(),
                    'journal_event_type_id' => $journal_event_type_id,
                    'prime_account_holder_id' => $user->account_holder_id,
                ]);

                //Set FinanceTypes
                $finance_type_asset_id = FinanceType::getIdByName('Asset', true);
                if( !$finance_type_asset_id ) return ['errors' => sprintf('Finance type Asset could not be set')];

                //Set MediumTypes

                $medium_type_giftcodes_id = MediumType::getIdByName('Gift Codes', true);
                if( !$medium_type_giftcodes_id ) return ['errors' => sprintf('MediumType Gift Codes could not be set')];

                $medium_type_monies_id = MediumType::getIdByName('Monies', true);
                if( !$medium_type_monies_id ) return ['errors' => sprintf('MediumType Monies could not be set')];

                $response['postings']['transfer_code'] = Account::postings(
                    $user->account_holder_id,
                    'Gift Codes Available',
                    $finance_type_asset_id,
                    $medium_type_giftcodes_id,
                    $code->merchant->account_holder_id,
                    'Gift Codes Available',
                    $finance_type_asset_id,
                    $medium_type_giftcodes_id,
                    $journal_event_id,
                    $code->sku_value,
                    1, //qty
                    null, // medium_info
                    $code->id, // medium_info_id
                    $currency_id
                );
            } catch (Exception $e)	{
                $response['errors'] = "Error while Models\Traits\Redeemable::_transfer_giftcodes_to_merchant_no_transaction. Message: {$e->getMessage()} in line: {$e->getLine()}";
            }

            return $response;
    }
}
