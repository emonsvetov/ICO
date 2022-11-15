<?php
namespace App\Services;

use App\Models\InvoiceJournalEvent;
use App\Models\JournalEventType;
use App\Models\JournalEvent;
use App\Models\FinanceType;
use App\Models\MediumType;
use App\Models\Currency;
use App\Models\Program;
use App\Models\Invoice;
use App\Models\Account;
use App\Models\Owner;

class InvoicePaymentService
{
    public $invoice = null;
    public function __construct(Invoice $invoice)   {
        $this->invoice = $invoice;
    }

    public function program_pays_for_deposit_fee($program_id, $amount, $notes)  {
        $notes = strip_tags ( $notes, ALLOWED_HTML_TAGS );
        $owner_account_holder_id = Owner::find(1)->account_holder_id;
        $currency_id = Currency::getIdByType(config('global.default_currency'), true);
        $program = Program::find($program_id);
        $program_account_holder_id = $program->account_holder_id;
        $user_account_holder_id = auth()->user()->account_holder_id;

        //Start transaction
        $journal_event_id = 0;

        $medium_type_monies_id = MediumType::getIdByName('Monies', true);
        $finance_type_asset_id = FinanceType::getIdByName('Asset', true);
        $journal_event_type_id = JournalEventType::getIdByType( 'Program pays for deposit fee' );

        //create JouralEvent
        $journal_event_id = JournalEvent::insertGetId([
            'journal_event_type_id' => $journal_event_type_id,
            'notes' => $notes,
            'prime_account_holder_id' => $user_account_holder_id,
            'created_at' => now()
        ]);

        //create postings
        $postings = Account::postings(
            $owner_account_holder_id,
            'Cash',
            $finance_type_asset_id,
            $medium_type_monies_id,
            $program_account_holder_id,
            'Monies Due to Owner',
            $finance_type_asset_id,
            $medium_type_monies_id,
            $journal_event_id,
            $amount,
            1, //qty
            null, // medium_info
            null, // medium_info_id
            $currency_id
        );

        if( isset($postings['success']) ) {
            InvoiceJournalEvent::create([
                'journal_event_id' => $journal_event_id,
                'invoice_id' => $this->invoice->id
            ]);
            return true;
        }
    }

    public function program_pays_for_monies_pending($program_id, $amount, $notes, $email_account_holder = true)  {
        $notes = strip_tags ( $notes, ALLOWED_HTML_TAGS );
        $owner_account_holder_id = Owner::find(1)->account_holder_id;
        $currency_id = Currency::getIdByType(config('global.default_currency'), true);
        $program = Program::find($program_id);
        $program_account_holder_id = $program->account_holder_id;
        $user_account_holder_id = auth()->user()->account_holder_id;

        //Start transaction
        $journal_event_id = 0;

        $medium_type_monies_id = MediumType::getIdByName('Monies', true);
        $finance_type_asset_id = FinanceType::getIdByName('Asset', true);
        $finance_type_liability_id = FinanceType::getIdByName('Liability', true);
        $journal_event_type_id = JournalEventType::getIdByType( 'Program pays for monies pending' );

        //create JouralEvent
        $journal_event_id = JournalEvent::insertGetId([
            'journal_event_type_id' => $journal_event_type_id,
            'notes' => $notes,
            'prime_account_holder_id' => $user_account_holder_id,
            'created_at' => now()
        ]);

        //create program postings
        $postings_a = Account::postings(
            $program_account_holder_id,
            'Monies Pending',
            $finance_type_liability_id,
            $medium_type_monies_id,
            $program_account_holder_id,
            'Monies Available',
            $finance_type_asset_id,
            $medium_type_monies_id,
            $journal_event_id,
            $amount,
            1, //qty
            null, // medium_info
            null, // medium_info_id
            $currency_id
        );

        //create owner postings
        $postings_b = Account::postings(
            $owner_account_holder_id,
            'Cash',
            $finance_type_asset_id,
            $medium_type_monies_id,
            $program_account_holder_id,
            'Monies Due to Owner',
            $finance_type_liability_id,
            $medium_type_monies_id,
            $journal_event_id,
            $amount,
            1, //qty
            null, // medium_info
            null, // medium_info_id
            $currency_id
        );

        if( isset($postings_a['success']) && isset($postings_b['success']) ) {
            InvoiceJournalEvent::create([
                'journal_event_id' => $journal_event_id,
                'invoice_id' => $this->invoice->id
            ]);
            return true;
        }
    }

    public function program_pays_for_admin_fee($program_id, $amount, $notes)  {
        $notes = strip_tags ( $notes, ALLOWED_HTML_TAGS );
        $owner_account_holder_id = Owner::find(1)->account_holder_id;
        $currency_id = Currency::getIdByType(config('global.default_currency'), true);
        $program = Program::find($program_id);
        $program_account_holder_id = $program->account_holder_id;
        $user_account_holder_id = auth()->user()->account_holder_id;

        //Start transaction
        $journal_event_id = 0;

        $monies = MediumType::getIdByName('Monies', true);
        $asset = FinanceType::getIdByName('Asset', true);
        $journal_event_type_id = JournalEventType::getIdByType( 'Program pays for admin fee' );

        //create JouralEvent
        $journal_event_id = JournalEvent::insertGetId([
            'journal_event_type_id' => $journal_event_type_id,
            'notes' => $notes,
            'prime_account_holder_id' => $user_account_holder_id,
            'created_at' => now()
        ]);

        //create postings
        $postings = Account::postings(
            $owner_account_holder_id,
            'Cash',
            $asset,
            $monies,
            $program_account_holder_id,
            'Monies Due to Owner',
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
            InvoiceJournalEvent::create([
                'journal_event_id' => $journal_event_id,
                'invoice_id' => $this->invoice->id
            ]);
            return true;
        }
    }

    public function program_pays_for_setup_fee($program_id, $amount, $notes)  {
        $notes = strip_tags ( $notes, ALLOWED_HTML_TAGS );
        $owner_account_holder_id = Owner::find(1)->account_holder_id;
        $currency_id = Currency::getIdByType(config('global.default_currency'), true);
        $program = Program::find($program_id);
        $program_account_holder_id = $program->account_holder_id;
        $user_account_holder_id = auth()->user()->account_holder_id;

        //Start transaction
        $journal_event_id = 0;

        $monies = MediumType::getIdByName('Monies', true);
        $asset = FinanceType::getIdByName('Asset', true);
        $journal_event_type_id = JournalEventType::getIdByType( 'Program pays for setup fee' );

        //create JouralEvent
        $journal_event_id = JournalEvent::insertGetId([
            'journal_event_type_id' => $journal_event_type_id,
            'notes' => $notes,
            'prime_account_holder_id' => $user_account_holder_id,
            'created_at' => now()
        ]);

        //create postings
        $postings = Account::postings(
            $owner_account_holder_id,
            'Cash',
            $asset,
            $monies,
            $program_account_holder_id,
            'Monies Due to Owner',
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
            InvoiceJournalEvent::create([
                'journal_event_id' => $journal_event_id,
                'invoice_id' => $this->invoice->id
            ]);
            return true;
        }
    }

    public function program_pays_for_usage_fee($program_id, $amount, $notes)  {
        $notes = strip_tags ( $notes, ALLOWED_HTML_TAGS );
        $owner_account_holder_id = Owner::find(1)->account_holder_id;
        $currency_id = Currency::getIdByType(config('global.default_currency'), true);
        $program = Program::find($program_id);
        $program_account_holder_id = $program->account_holder_id;
        $user_account_holder_id = auth()->user()->account_holder_id;

        //Start transaction
        $journal_event_id = 0;

        $monies = MediumType::getIdByName('Monies', true);
        $asset = FinanceType::getIdByName('Asset', true);
        $journal_event_type_id = JournalEventType::getIdByType( 'Program pays for monthly usage fee' );

        //create JouralEvent
        $journal_event_id = JournalEvent::insertGetId([
            'journal_event_type_id' => $journal_event_type_id,
            'notes' => $notes,
            'prime_account_holder_id' => $user_account_holder_id,
            'created_at' => now()
        ]);

        //create postings
        $postings = Account::postings(
            $owner_account_holder_id,
            'Cash',
            $asset,
            $monies,
            $program_account_holder_id,
            'Monies Due to Owner',
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
            InvoiceJournalEvent::create([
                'journal_event_id' => $journal_event_id,
                'invoice_id' => $this->invoice->id
            ]);
            return true;
        }
    }

    public function program_pays_for_fixed_fee($program_id, $amount, $notes)  {
        $notes = strip_tags ( $notes, ALLOWED_HTML_TAGS );
        $owner_account_holder_id = Owner::find(1)->account_holder_id;
        $currency_id = Currency::getIdByType(config('global.default_currency'), true);
        $program = Program::find($program_id);
        $program_account_holder_id = $program->account_holder_id;
        $user_account_holder_id = auth()->user()->account_holder_id;

        //Start transaction
        $journal_event_id = 0;

        $monies = MediumType::getIdByName('Monies', true);
        $asset = FinanceType::getIdByName('Asset', true);
        $journal_event_type_id = JournalEventType::getIdByType( 'Program pays for fixed fee' );

        //create JouralEvent
        $journal_event_id = JournalEvent::insertGetId([
            'journal_event_type_id' => $journal_event_type_id,
            'notes' => $notes,
            'prime_account_holder_id' => $user_account_holder_id,
            'created_at' => now()
        ]);

        //create postings
        $postings = Account::postings(
            $owner_account_holder_id,
            'Cash',
            $asset,
            $monies,
            $program_account_holder_id,
            'Monies Due to Owner',
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
            InvoiceJournalEvent::create([
                'journal_event_id' => $journal_event_id,
                'invoice_id' => $this->invoice->id
            ]);
            return true;
        }
    }

    public function program_pays_for_points($program_id, $amount, $notes)  {
        $notes = strip_tags ( $notes, ALLOWED_HTML_TAGS );
        $owner_account_holder_id = Owner::find(1)->account_holder_id;
        $currency_id = Currency::getIdByType(config('global.default_currency'), true);
        $program = Program::find($program_id);
        $program_account_holder_id = $program->account_holder_id;
        $user_account_holder_id = auth()->user()->account_holder_id;

        //Start transaction
        $journal_event_id = 0;

        $monies = MediumType::getIdByName('Monies', true);
        $asset = FinanceType::getIdByName('Asset', true);
        $journal_event_type_id = JournalEventType::getIdByType( 'Program pays for points' );

        //create JouralEvent
        $journal_event_id = JournalEvent::insertGetId([
            'journal_event_type_id' => $journal_event_type_id,
            'notes' => $notes,
            'prime_account_holder_id' => $user_account_holder_id,
            'created_at' => now()
        ]);

        //create postings
        $postings = Account::postings(
            $owner_account_holder_id,
            'Cash',
            $asset,
            $monies,
            $program_account_holder_id,
            'Monies Due to Owner',
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
            InvoiceJournalEvent::create([
                'journal_event_id' => $journal_event_id,
                'invoice_id' => $this->invoice->id
            ]);
            return true;
        }
    }

    public function program_pays_for_points_transaction_fee($program_id, $amount, $notes)  {
        $notes = strip_tags ( $notes, ALLOWED_HTML_TAGS );
        $owner_account_holder_id = Owner::find(1)->account_holder_id;
        $currency_id = Currency::getIdByType(config('global.default_currency'), true);
        $program = Program::find($program_id);
        $program_account_holder_id = $program->account_holder_id;
        $user_account_holder_id = auth()->user()->account_holder_id;

        //Start transaction
        $journal_event_id = 0;

        $monies = MediumType::getIdByName('Monies', true);
        $asset = FinanceType::getIdByName('Asset', true);
        $journal_event_type_id = JournalEventType::getIdByType( 'Program pays for points transaction fee' );

        //create JouralEvent
        $journal_event_id = JournalEvent::insertGetId([
            'journal_event_type_id' => $journal_event_type_id,
            'notes' => $notes,
            'prime_account_holder_id' => $user_account_holder_id,
            'created_at' => now()
        ]);

        //create postings
        $postings = Account::postings(
            $owner_account_holder_id,
            'Cash',
            $asset,
            $monies,
            $program_account_holder_id,
            'Monies Due to Owner',
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
            InvoiceJournalEvent::create([
                'journal_event_id' => $journal_event_id,
                'invoice_id' => $this->invoice->id
            ]);
            return true;
        }
    }
}
