<?php
namespace App\Services\Program\Traits;

use App\Services\Program\JournalBackdatePostingService;
use App\Models\JournalEventType;
use App\Models\JournalEvent;
use App\Models\FinanceType;
use App\Models\MediumType;
use App\Models\Currency;
use App\Models\Posting;
use App\Models\Account;
use App\Models\Owner;

trait ChargeFeeTrait {

    private function chargeFee($journal_event_type, $program, $amount, $quantity)    {
        
        // $owner_account_holder_id = Owner::find(1)->account_holder_id;
        $currency_id = Currency::getIdByType(config('global.default_currency'), true);
        $program_account_holder_id = ( int ) $program->account_holder_id;
        $monies = MediumType::getIdByName('Monies', true);
        $liability = FinanceType::getIdByName('Liability', true);
        $asset = FinanceType::getIdByName('Asset', true);
        $journal_event_type_id = JournalEventType::getIdByType( 'Charge program for admin fee', true );
        $journal_event_id = JournalEvent::insertGetId([
			'journal_event_type_id' => $journal_event_type_id,
			'created_at' => now()
		]);
        $postings = true;
        $postings = Account::postings(
			$program_account_holder_id,
			'Monies Due to Owner',
			$asset,
			$monies,
			$program_account_holder_id,
			'Monies Fees',
			$liability,
			$monies,
			$journal_event_id,
			$amount,
			$quantity, //qty
			null, //medium_info
			null, // medium_info_id
			$currency_id
		);
        if( !$postings )   { //TODO
            (new JournalBackdatePostingService)->backdatePosting($journal_event_id, \Carbon\Carbon::yesterday()->format('y-m-d'));
        }
        return $postings;
    }

    public function chargeForAdminFee($program, $amount, $quantity = 1)    {
        return self::chargeFee('Charge program for admin fee', $program, $amount, $quantity);
    }

    public function chargeForMonthlyUsageFee($program, $amount, $quantity = 1)    {
        return self::chargeFee('Charge program for monthly usage fee', $program, $amount, $quantity);
    }

    public function chargeForFixedFee($program, $amount, $quantity = 1)    {
        return self::chargeFee('Charge program for fixed fee', $program, $amount, $quantity);
    }
}