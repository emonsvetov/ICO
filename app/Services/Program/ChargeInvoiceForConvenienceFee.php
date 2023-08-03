<?php
namespace App\Services\Program;

use App\Models\JournalEventType;
use App\Models\JournalEvent;
use App\Models\FinanceType;
use App\Models\MediumType;
use App\Models\Currency;
use App\Models\Invoice;
use App\Models\Account;

class ChargeInvoiceForConvenienceFee
{
    public function process(Invoice $invoice, $user, $program, $amount)
    {
        $currency_id = Currency::getIdByType(config('global.default_currency'), true);
        $program_account_holder_id = ( int ) $program->account_holder_id;
        $prime_account_holder_id = ( int ) $user->account_holder_id;
        $monies = MediumType::getIdByName('Monies', true);
        $liability = FinanceType::getIdByName('Liability', true);
        $asset = FinanceType::getIdByName('Asset', true);
        $journal_event_type_id = JournalEventType::getIdByType( 'Charge program for convenience fee', true );
        $journal_event_id = JournalEvent::insertGetId([
			'journal_event_type_id' => $journal_event_type_id,
			'prime_account_holder_id' => $prime_account_holder_id,
			'created_at' => now()
		]);
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
			1, //qty
			null, //medium_info
			null, // medium_info_id
			$currency_id
		);
        if( $postings )   {
            $invoice->journal_events()->sync( [ $journal_event_id ], false);
        }
        return $invoice;
    }
}
