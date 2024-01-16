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
use App\Models\Account;
use App\Models\Owner;

trait ProgramPaymentReversalTrait {
    //Program Payment Reversal

    public function reversePayment($program, $invoice, $data)   {
        $this->program = $program;
        $result = [];
        $payment_kind = $data['event_type'];
        $amount = $data['amount'];
        $notes = $data['notes'];
        $invoice_id = $invoice->id;
        $journal_event_id = $data['journal_event_id'];

        $user_account_holder_id = auth()->user()->account_holder_id;
        $program_account_holder_id = $program->account_holder_id;

        switch ($payment_kind) {
            case 'Program pays for points' :
                $result = $this->reversal_program_pays_for_points ($user_account_holder_id, $program_account_holder_id, $amount, $notes, $invoice_id, $journal_event_id );
                $result['success'] = "Reversal program paid for points successfully...";
            break;
            case 'Program pays for setup fee' :
                $result = $this->reversal_program_pays_for_setup_fee ($user_account_holder_id, $program_account_holder_id, $amount, $notes, $invoice_id, $journal_event_id );
                $result['success'] = "Reversal program paid for setup fee successfully...";
            break;
            case 'Program pays for admin fee' :
                $result = $this->reversal_program_pays_for_admin_fee ($user_account_holder_id, $program_account_holder_id, $amount, $notes, $invoice_id, $journal_event_id );
                $result['success'] = "Reversal program paid for admin fee successfully...";
            break;
            case 'Program pays for monthly usage fee' :
                $result = $this->reversal_program_pays_for_usage_fee ($user_account_holder_id, $program_account_holder_id, $amount, $notes, $invoice_id, $journal_event_id );
                $result['success'] = "Reversal program paid for usage fee successfully...";
            break;
            case 'Program pays for deposit fee' :
                $result = $this->reversal_program_pays_for_deposit_fee ($user_account_holder_id, $program_account_holder_id, $amount, $notes, $invoice_id, $journal_event_id );
                $result['success'] = "Reversal program paid for deposit fee successfully...";
                break;
            case 'Program pays for fixed fee' :
                $result = $this->reversal_program_pays_for_fixed_fee ($user_account_holder_id, $program_account_holder_id, $amount, $notes, $invoice_id, $journal_event_id );
                $result['success'] = "Reversal program paid for fixed fee successfully...";
            break;
            case 'Program pays for convenience fee' :
                $result = $this->reversal_program_pays_for_convenience_fee ($user_account_holder_id, $program_account_holder_id, $amount, $notes, $invoice_id, $journal_event_id );
                $result['success'] = "Reversal program paid for convenience fee successfully...";
                break;
            case 'Program pays for monies pending' :
                $result = $this->reversal_program_pays_for_monies_pending ($user_account_holder_id, $program_account_holder_id, $amount, $notes, $invoice_id, $journal_event_id );
                $result['success'] = "Reversal program paid for monies pending successfully...";
            break;
            case 'Program pays for points transaction fee' :
                $result = $this->reversal_program_pays_for_points_transaction_fee ($user_account_holder_id, $program_account_holder_id, $amount, $notes, $invoice_id, $journal_event_id );
                $result['success'] = "Reversal program paid for points transaction fee successfully...";
            break;
        }
        return $result;
    }

    public function reversal_program_pays_for($payment_kind, $user_account_holder_id, $program_account_holder_id, $amount, $notes, $invoice_id, $parent_journal_event_id)  {

        $owner_account_holder_id = Owner::find(1)->account_holder_id;
        $currency_id = Currency::getIdByType(config('global.default_currency'), true);

        //Start transaction
        $journal_event_id = 0;

        $journal_event_type_id = JournalEventType::getIdByType( $payment_kind, true );

        if( !$owner_account_holder_id || !$currency_id || !$journal_event_type_id)    {
            return ['errors' => 'Cannot process as invalid data was found'];
        }

        //create JouralEvent
        $journal_event_id = JournalEvent::insertGetId([
            'journal_event_type_id' => $journal_event_type_id,
            'parent_journal_event_id' => $parent_journal_event_id,
            'notes' => $notes,
            'prime_account_holder_id' => $user_account_holder_id,
            'created_at' => now()
        ]);

        $asset = FinanceType::getIdByName('Asset', true);
        $monies = MediumType::getIdByName('Monies', true);

        //create program postings
        $postings = Account::postings(
            $program_account_holder_id,
            'Monies Due to Owner',
            $asset,
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

        if(isset($postings['success'])) {
            $postings['invoice_journal_event'] = InvoiceJournalEvent::create([
                'journal_event_id' => $journal_event_id,
                'invoice_id' => $invoice_id
            ]);
        }
        return $postings;
    }

    public function reversal_program_pays_for_points($user_account_holder_id, $program_account_holder_id, $amount, $notes, $invoice_id, $parent_journal_event_id)   {
        return $this->reversal_program_pays_for('Reversal program pays for points', $user_account_holder_id, $program_account_holder_id, $amount, $notes, $invoice_id, $parent_journal_event_id);
    }

    public function reversal_program_pays_for_setup_fee($user_account_holder_id, $program_account_holder_id, $amount, $notes, $invoice_id, $parent_journal_event_id)   {
        return $this->reversal_program_pays_for('Reversal program pays for setup fee', $user_account_holder_id, $program_account_holder_id, $amount, $notes, $invoice_id, $parent_journal_event_id);
    }

    public function reversal_program_pays_for_admin_fee($user_account_holder_id, $program_account_holder_id, $amount, $notes, $invoice_id, $parent_journal_event_id )   {
        return $this->reversal_program_pays_for('Reversal program pays for admin fee', $user_account_holder_id, $program_account_holder_id, $amount, $notes, $invoice_id, $parent_journal_event_id);
    }

    public function reversal_program_pays_for_usage_fee($user_account_holder_id, $program_account_holder_id, $amount, $notes, $invoice_id, $parent_journal_event_id )   {
        return $this->reversal_program_pays_for('Reversal program pays for monthly usage fee', $user_account_holder_id, $program_account_holder_id, $amount, $notes, $invoice_id, $parent_journal_event_id);
    }

    public function reversal_program_pays_for_deposit_fee($user_account_holder_id, $program_account_holder_id, $amount, $notes, $invoice_id, $parent_journal_event_id )   {
        return $this->reversal_program_pays_for('Reversal program pays for deposit fee', $user_account_holder_id, $program_account_holder_id, $amount, $notes, $invoice_id, $parent_journal_event_id);
    }

    public function reversal_program_pays_for_fixed_fee($user_account_holder_id, $program_account_holder_id, $amount, $notes, $invoice_id, $parent_journal_event_id )   {
        return $this->reversal_program_pays_for('Reversal program pays for fixed fee', $user_account_holder_id, $program_account_holder_id, $amount, $notes, $invoice_id, $parent_journal_event_id);
    }

    public function reversal_program_pays_for_convenience_fee($user_account_holder_id, $program_account_holder_id, $amount, $notes, $invoice_id, $parent_journal_event_id )   {
        return $this->reversal_program_pays_for('Reversal program pays for convenience fee', $user_account_holder_id, $program_account_holder_id, $amount, $notes, $invoice_id, $parent_journal_event_id);
    }

    public function reversal_program_pays_for_monies_pending($user_account_holder_id, $program_account_holder_id, $amount, $notes, $invoice_id, $parent_journal_event_id )   {

        $owner_account_holder_id = Owner::find(1)->account_holder_id;
        $currency_id = Currency::getIdByType(config('global.default_currency'), true);

        //Start transaction
        $journal_event_id = 0;
        $result = [];

        $monies = MediumType::getIdByName('Monies', true);
        $asset = FinanceType::getIdByName('Asset', true);
        $liability = FinanceType::getIdByName('Liability', true);
        $journal_event_type_id = JournalEventType::getIdByType( 'Reversal program pays for monies pending', true);

        //create JouralEvent
        $journal_event_id = JournalEvent::insertGetId([
            'journal_event_type_id' => $journal_event_type_id,
            'parent_journal_event_id' => $parent_journal_event_id,
            'notes' => $notes,
            'prime_account_holder_id' => $user_account_holder_id,
            'created_at' => now()
        ]);

        $result['postings'][] = Account::postings(
            $program_account_holder_id,
            'Monies Available',
            $asset,
            $monies,
            $program_account_holder_id,
            'Monies Pending',
            $liability,
            $monies,
            $journal_event_id,
            $amount,
            1, //qty
            null, // medium_info
            null, // medium_info_id
            $currency_id
        );

        $result['postings'][] = Account::postings(
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

        if(isset($result['postings']) && sizeof($result['postings']) == 2) {
            $invoice_journal_event_id = InvoiceJournalEvent::insertGetId([
                'journal_event_id' => $journal_event_id,
                'invoice_id' => $invoice_id
            ]);
            $result['invoice_journal_event_id'] = $invoice_journal_event_id;
        }
        return $result;
    }

    public function reversal_program_pays_for_points_transaction_fee($user_account_holder_id, $program_account_holder_id, $amount, $notes, $invoice_id, $parent_journal_event_id )   {
        return $this->reversal_program_pays_for('Reversal program pays for points transaction fee', $user_account_holder_id, $program_account_holder_id, $amount, $notes, $invoice_id, $parent_journal_event_id);
    }
}
