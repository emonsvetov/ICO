<?php
namespace App\Services\Program;

use App\Models\JournalEventType;
use App\Models\JournalEvent;
use App\Models\FinanceType;
use App\Models\MediumType;
use App\Models\Currency;
use App\Models\Account;

class TransferMoniesService
{
    public function transferMonies($user_account_holder_id, $program_account_holder_id, $new_program_account_holder_id, $amount)    {
        $currency_id = Currency::getIdByType(config('global.default_currency'), true);

        //Start transaction
        $journal_event_id = 0;
        $journal_event_type_id = JournalEventType::getIdByType( 'Program transfers monies available', true );

        //create JouralEvent
        $journal_event_id = JournalEvent::insertGetId([
            'journal_event_type_id' => $journal_event_type_id,
            'prime_account_holder_id' => $user_account_holder_id,
            'created_at' => now()
        ]);

        $monies = MediumType::getIdByName('Monies', true);
        $asset = FinanceType::getIdByName('Asset', true);

        //create program postings
        $postings = Account::postings(
            $program_account_holder_id,
            'Monies Available',
            $asset,
            $monies,
            $new_program_account_holder_id,
            'Monies Available',
            $asset,
            $monies,
            $journal_event_id,
            $amount,
            1, //qty
            null, // medium_info
            null, // medium_info_id
            $currency_id
        );
        if( isset($postings['success']) ) {
            return true;
        }
    }
}