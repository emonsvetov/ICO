<?php
namespace App\Services\Program\Traits;
use Illuminate\Support\Str;
use App\Models\InvoiceJournalEvent;
use App\Models\JournalEventType;
use App\Models\JournalEvent;
use App\Models\FinanceType;
use App\Models\MediumType;
use App\Models\Currency;
use App\Models\Posting;
// use App\Models\Invoice;
use App\Models\Account;
use App\Models\Owner;

trait ProgramPaymentTrait {
       //Program Payment Submit

       public function submitPayments($program, $data)   {
        $this->program = $program;
        $result = [];
        // return $data;
        $payment_kind = $data['payment_kind'];
        $amount = $data['amount'];
        $notes = $data['notes'];
        $invoice_id = $data['invoice_id'];

        $program_account_holder_id = $program->account_holder_id;
        $user_account_holder_id = auth()->user()->account_holder_id;

        switch ($payment_kind) {
            case 'program_pays_for_points' :
                $result = $this->program_pays_for_points ($user_account_holder_id, $program_account_holder_id, $amount, $notes, $invoice_id );
                $result['success'] = "Program paid for points successfully...";
                break;
            case 'program_pays_for_setup_fee' :
                $result = $this->program_pays_for_setup_fee ($user_account_holder_id, $program_account_holder_id, $amount, $notes, $invoice_id );
                $result['success'] = "Program paid for setup fee successfully...";
                break;
            case 'program_pays_for_admin_fee' :
                $result = $this->program_pays_for_admin_fee ($user_account_holder_id, $program_account_holder_id, $amount, $notes, $invoice_id );
                $result['success'] = "Program paid for admin fee successfully...";
                break;
            case 'program_pays_for_usage_fee' :
                $result = $this->program_pays_for_usage_fee ($user_account_holder_id, $program_account_holder_id, $amount, $notes, $invoice_id );
                $result['success'] = "Program paid for usage fee successfully...";
                break;
            case 'program_pays_for_deposit_fee' :
                $result = $this->program_pays_for_deposit_fee ($user_account_holder_id, $program_account_holder_id, $amount, $notes, $invoice_id );
                $result['success'] = "Program paid for deposit fee successfully...";
                break;
            case 'program_pays_for_fixed_fee' :
                $result = $this->program_pays_for_fixed_fee ($user_account_holder_id, $program_account_holder_id, $amount, $notes, $invoice_id );
                $result['success'] = "Program paid for fixed fee successfully...";
                break;
            case 'program_pays_for_convenience_fee' :
                $result = $this->program_pays_for_convenience_fee ($user_account_holder_id, $program_account_holder_id, $amount, $notes, $invoice_id );
                $result['success'] = "Program paid for convenience fee successfully...";
                break;
            case 'program_pays_for_monies_pending' :
                $result = $this->program_pays_for_monies_pending ($user_account_holder_id, $program_account_holder_id, $amount, $notes, $invoice_id );
                $result['success'] = "Program paid for monies pending successfully...";
                break;
            case 'program_refunds_for_monies_pending' :
                $result = $this->program_refunds_for_monies_pending ($user_account_holder_id, $program_account_holder_id, $amount, $notes, $invoice_id );
                $result['success'] = "Program refunded for monies pending successfully...";
                break;
            case 'program_pays_for_points_transaction_fee' :
                $result = $this->program_pays_for_points_transaction_fee ($user_account_holder_id, $program_account_holder_id, $amount, $notes, $invoice_id );
                $result['success'] = "Program paid for points transaction fee successfully...";
                break;
        }
        return $result;
    }

    public function program_pays_for($payment_kind, $user_account_holder_id, $program_account_holder_id, $amount, $notes, $invoice_id )  {

        $owner_account_holder_id = Owner::find(1)->account_holder_id;
        $currency_id = Currency::getIdByType(config('global.default_currency'), true);

        //Start transaction
        $journal_event_id = 0;
        $result = [];

        $monies = MediumType::getIdByName('Monies', true);
        $asset = FinanceType::getIdByName('Asset', true);

        $journal_event_type_id = JournalEventType::getIdByType( $payment_kind, true );

        //create JouralEvent
        $journal_event_id = JournalEvent::insertGetId([
            'journal_event_type_id' => $journal_event_type_id,
            'notes' => $notes,
            'prime_account_holder_id' => $user_account_holder_id,
            'created_at' => now()
        ]);

        //create program postings
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

        $result['postings'][] = $postings;

        if(isset($postings['success'])) {
            $result['invoice_journal_event_id'] = InvoiceJournalEvent::insertGetId([
                'journal_event_id' => $journal_event_id,
                'invoice_id' => $invoice_id
            ]);
        }
        return $result;
    }


    public function program_pays_for_points($user_account_holder_id, $program_account_holder_id, $amount, $notes, $invoice_id )   {
        return $this->program_pays_for('Program pays for points', $user_account_holder_id, $program_account_holder_id, $amount, $notes, $invoice_id);
    }

    public function program_pays_for_setup_fee($user_account_holder_id, $program_account_holder_id, $amount, $notes, $invoice_id )   {
        return $this->program_pays_for('Program pays for setup fee', $user_account_holder_id, $program_account_holder_id, $amount, $notes, $invoice_id);
    }

    public function program_pays_for_admin_fee($user_account_holder_id, $program_account_holder_id, $amount, $notes, $invoice_id )   {
        return $this->program_pays_for('Program pays for admin fee', $user_account_holder_id, $program_account_holder_id, $amount, $notes, $invoice_id);
    }

    public function program_pays_for_usage_fee($user_account_holder_id, $program_account_holder_id, $amount, $notes, $invoice_id )   {
        return $this->program_pays_for('Program pays for monthly usage fee', $user_account_holder_id, $program_account_holder_id, $amount, $notes, $invoice_id);
    }

    public function program_pays_for_deposit_fee($user_account_holder_id, $program_account_holder_id, $amount, $notes, $invoice_id )   {
        return $this->program_pays_for('Program pays for deposit fee', $user_account_holder_id, $program_account_holder_id, $amount, $notes, $invoice_id);
    }

    public function program_pays_for_fixed_fee($user_account_holder_id, $program_account_holder_id, $amount, $notes, $invoice_id )   {
        return $this->program_pays_for('Program pays for fixed fee', $user_account_holder_id, $program_account_holder_id, $amount, $notes, $invoice_id);
    }

    public function program_pays_for_convenience_fee($user_account_holder_id, $program_account_holder_id, $amount, $notes, $invoice_id )   {
        return $this->program_pays_for('Program pays for convenience fee', $user_account_holder_id, $program_account_holder_id, $amount, $notes, $invoice_id);
    }

    public function program_pays_for_monies_pending($user_account_holder_id, $program_account_holder_id, $amount, $notes, $invoice_id )   {

        $owner_account_holder_id = Owner::find(1)->account_holder_id;
        $currency_id = Currency::getIdByType(config('global.default_currency'), true);

        //Start transaction
        $journal_event_id = 0;

        $monies = MediumType::getIdByName('Monies', true);
        $asset = FinanceType::getIdByName('Asset', true);
        $liability = FinanceType::getIdByName('Liability', true);
        $journal_event_type_id = JournalEventType::getIdByType( 'Program pays for monies pending' );

        //create JouralEvent
        $journal_event_id = JournalEvent::insertGetId([
            'journal_event_type_id' => $journal_event_type_id,
            'notes' => $notes,
            'prime_account_holder_id' => $user_account_holder_id,
            'created_at' => now()
        ]);

        $result = [];

        $postings_A = Account::postings(
            $program_account_holder_id,
            'Monies Pending',
            $liability,
            $monies,
            $program_account_holder_id,
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

        $result['postings'][] = $postings_A;

        $postings_B = Account::postings(
            $owner_account_holder_id,
            'Cash',
            $asset,
            $monies,
            $program_account_holder_id,
            'Monies Due to Owner',
            $liability,
            $monies,
            $journal_event_id,
            $amount,
            1, //qty
            null, // medium_info
            null, // medium_info_id
            $currency_id
        );

        $result['postings'][] = $postings_B;

        if(isset($postings_A['success']) && isset($postings_B['success'])) {
            $result['invoice_journal_event_id'] = InvoiceJournalEvent::insertGetId([
                'journal_event_id' => $journal_event_id,
                'invoice_id' => $invoice_id
            ]);
        }
        return $result;
    }

    public function program_refunds_for_monies_pending($user_account_holder_id, $program_account_holder_id, $amount, $notes, $invoice_id )   {
        $owner_account_holder_id = Owner::find(1)->account_holder_id;
        $currency_id = Currency::getIdByType(config('global.default_currency'), true);

        //Start transaction
        $journal_event_id = 0;

        $monies = MediumType::getIdByName('Monies', true);
        $asset = FinanceType::getIdByName('Asset', true);
        $liability = FinanceType::getIdByName('Liability', true);
        $journal_event_type_id = JournalEventType::getIdByType( 'Program refunds for monies pending' );

        //create JouralEvent
        $journal_event_id = JournalEvent::insertGetId([
            'journal_event_type_id' => $journal_event_type_id,
            'notes' => $notes,
            'created_at' => now()
        ]);

        $result = [];

        $postings_A = Account::postings(
            $program_account_holder_id,
            'Monies Available',
            $liability,
            $monies,
            $program_account_holder_id,
            'Monies Pending',
            $asset,
            $monies,
            $journal_event_id,
            $amount,
            1, //qty
            null, // medium_info
            null, // medium_info_id
            $currency_id
        );

        $result['postings'][] = $postings_A;

        $postings_B = Account::postings(
            $program_account_holder_id,
            'Monies Due to Owner',
            $liability,
            $monies,
            $owner_account_holder_id,
            'Cash',
            $asset,
            $monies,
            $journal_event_id,
            $amount,
            1, //qty
            null, // medium_info
            null, // medium_info_id
            $currency_id
        );

        $result['postings'][] = $postings_B;

        if(isset($postings_A['success']) && isset($postings_B['success'])) {
            $result['invoice_journal_event_id'] = InvoiceJournalEvent::insertGetId([
                'journal_event_id' => $journal_event_id,
                'invoice_id' => $invoice_id
            ]);
        }

        return $result;
    }

    public function program_pays_for_points_transaction_fee($user_account_holder_id, $program_account_holder_id, $amount, $notes, $invoice_id )   {
        return $this->program_pays_for('Program pays for points transaction fee', $user_account_holder_id, $program_account_holder_id, $amount, $notes, $invoice_id);
    }
}
