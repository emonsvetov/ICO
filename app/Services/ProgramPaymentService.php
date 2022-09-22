<?php
namespace App\Services;
use App\Models\InvoiceJournalEvent;
use App\Models\JournalEventType;
use App\Models\JournalEvent;
use App\Models\FinanceType;
use App\Models\MediumType;
use App\Models\Currency;
use App\Models\Program;
use App\Models\Posting;
use App\Models\Account;
use App\Models\Owner;

class ProgramPaymentService
{
    public $program;
    public $program_account_holder_id;
    public $user_account_holder_id;

    public function getPayments($program)   {
        $pays_for_points = request()->get('pays_for_points', false);
        if( $pays_for_points ) {
            return $this->read_list_program_pays_for($program);
        }

        $payment_kinds = [
            // method_name => Payment Name
            "program_pays_for_points" => "Program Pays for Points", // JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_POINTS
            "program_pays_for_setup_fee" => "Program Pays for Setup Fee",
            "program_pays_for_admin_fee" => "Program Pays for Admin Fee",
            "program_pays_for_usage_fee" => "Program Pays for Usage Fee",
            "program_pays_for_deposit_fee" => "Program Pays for Deposit Fee",
            "program_pays_for_fixed_fee" => "Program Pays for Fixed Fee",
            "program_pays_for_convenience_fee" => "Program Pays for Convenience Fee",
            "program_pays_for_monies_pending" => "Program Pays for Monies Pending",
            "program_pays_for_points_transaction_fee" => "Program Pays for Points Transaction Fee",
            "program_refunds_for_monies_pending" => "Program Refunds for Monies Pending"
        ];
        // pr($payment_kinds);
        $invoiceService = new InvoiceService();
        $invoices = $invoiceService->index($program, false);
        
        return [
            'payment_kinds' => $payment_kinds,
            'invoices' => $invoices,
            'invoice_id' => request()->get('invoice_id', null),
        ];
    }

    public function read_list_program_pays_for($program)    {

        $sortby = request()->get('sortby', 'invoice_id');
        $direction = request()->get('direction', 'asc');
        $limit = request()->get('limit', config('global.paginate_limit'));
        $orderByRaw = "{$sortby} {$direction}";

        $query = Posting::query();
        $query->orderByRaw($orderByRaw);
        $query->select(
            'postings.posting_amount AS amount', 
            'postings.created_at AS date_paid', 
            'postings.is_credit',
            'accounts.account_holder_id',
            'journal_events.id AS journal_event_id',
            'journal_events.notes AS notes',
            'journal_event_types.type AS event_type',
            'invoices.id AS invoice_id'
        );
        $query->selectRaw("concat(invoices.key, '-', invoices.seq) as invoice_number");
        $query->join('accounts', 'accounts.id', '=', 'postings.account_id');
        $query->join('account_types', 'account_types.id', '=', 'accounts.account_type_id');
        $query->join('journal_events', 'journal_events.id', '=', 'postings.journal_event_id');
        $query->join('journal_event_types', 'journal_event_types.id', '=', 'journal_events.journal_event_type_id');
        $query->leftJoin('event_xml_data', 'event_xml_data.id', '=', 'journal_events.event_xml_data_id');
        $query->leftJoin('invoice_journal_event', 'invoice_journal_event.journal_event_id', '=', 'journal_events.id');
        $query->leftJoin('invoices', 'invoices.id', '=', 'invoice_journal_event.invoice_id');
        $query->leftJoin('journal_events AS reversals', 'reversals.parent_id', '=', 'journal_events.id');
        $query->where('accounts.account_holder_id', $program->account_holder_id);
        $query->where(function($query1) {
            $query1->orWhere(function($query2) {
                $query2->where('postings.is_credit', '=', 1);
                $query2->whereIn('journal_event_types.type', [
                    JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_POINTS,
                    JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_SETUP_FEE,
                    JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_ADMIN_FEE,
                    JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_MONIES_PENDING,
                    JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_FIXED_FEE,
                    JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_DEPOSIT_FEE,
                    JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_CONVENIENCE_FEE,
                    JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_MONTHLY_USAGE_FEE,
                    JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_POINTS_TRANSACTION_FEE
                ]);
            });
            $query1->orWhere(function($query2) {
                $query2->where('postings.is_credit', '=', 0);
                $query2->whereIn('journal_event_types.type', [
                    JournalEventType::JOURNAL_EVENT_TYPES_REFUND_PROGRAM_FOR_MONIES_PENDING
                ]);
            });
        });
        $query->where('account_types.name', '=', 'Monies Due to Owner');
        $query->whereNull('reversals.id');

        $result = $query->paginate($limit)->toArray();
        return $result;
    }

    public function submitPayments($program, $data)   {
        $this->program = $program;
        $result = [];
        switch ($data['payment_kind']) {
            case 'program_pays_for_points' :
                $result = $this->program_pays_for_points ( $amount, $notes, $invoice_id );
                $result['success'] = "Program paid for points successfully...";
                break;
            case 'program_pays_for_setup_fee' :
                $result = $this->program_pays_for_setup_fee ( $amount, $notes, $invoice_id );
                $result['success'] = "Program paid for setup fee successfully...";
                break;
            case 'program_pays_for_admin_fee' :
                $result = $this->program_pays_for_admin_fee ( $amount, $notes, $invoice_id );
                $result['success'] = "Program paid for admin fee successfully...";
                break;
            case 'program_pays_for_usage_fee' :
                $result = $this->program_pays_for_usage_fee ( $amount, $notes, $invoice_id );
                $result['success'] = "Program paid for usage fee successfully...";
                break;
            case 'program_pays_for_deposit_fee' :
                $result = $this->program_pays_for_deposit_fee ( $amount, $notes, $invoice_id );
                $result['success'] = "Program paid for deposit fee successfully...";
                break;
            case 'program_pays_for_fixed_fee' :
                $result = $this->program_pays_for_fixed_fee ( $amount, $notes, $invoice_id );
                $result['success'] = "Program paid for fixed fee successfully...";
                break;
            case 'program_pays_for_convenience_fee' :
                $result = $this->program_pays_for_convenience_fee ( $amount, $notes, $invoice_id );
                $result['success'] = "Program paid for convenience fee successfully...";
                break;
            case 'program_pays_for_monies_pending' :
                $result = $this->program_pays_for_monies_pending ( $amount, $notes, $invoice_id );
                $result['success'] = "Program paid for monies pending successfully...";
                break;
            case 'program_refunds_for_monies_pending' :
                $result = $this->program_refunds_for_monies_pending ( $amount, $notes, $invoice_id );
                $result['success'] = "Program refunded for monies pending successfully...";
                break;
            case 'program_pays_for_points_transaction_fee' :
                $result = $this->program_pays_for_points_transaction_fee ( $amount, $notes, $invoice_id );
                $result['success'] = "Program paid for points transaction fee successfully...";
                break;
        }
        return $result;
    }

    public function program_pays_for_points($program, $amount, $notes, $invoice_id )   {
        return $this->program_pays_for('Program pays for points', $program, $amount, $notes, $invoice_id);    
    }    
    
    public function program_pays_for_setup_fee($program, $amount, $notes, $invoice_id )   {
        return $this->program_pays_for('Program pays for setup fee', $program, $amount, $notes, $invoice_id);    
    }    
    
    public function program_pays_for_admin_fee($program, $amount, $notes, $invoice_id )   {
        return $this->program_pays_for('Program pays for admin fee', $program, $amount, $notes, $invoice_id);    
    }

    public function program_pays_for_usage_fee($program, $amount, $notes, $invoice_id )   {
        return $this->program_pays_for('Program pays for monthly usage fee', $program, $amount, $notes, $invoice_id);    
    }    
    
    public function program_pays_for_deposit_fee($program, $amount, $notes, $invoice_id )   {
        return $this->program_pays_for('Program pays for deposit fee', $program, $amount, $notes, $invoice_id);    
    }    
    
    public function program_pays_for_fixed_fee($program, $amount, $notes, $invoice_id )   {
        return $this->program_pays_for('Program pays for fixed fee', $program, $amount, $notes, $invoice_id);    
    }

    public function program_pays_for_convenience_fee($program, $amount, $notes, $invoice_id )   {
        return $this->program_pays_for('Program pays for convenience fee', $program, $amount, $notes, $invoice_id);    
    }

    public function program_pays_for_monies_pending($program, $amount, $notes, $invoice_id )   {
        $program_account_holder_id = $program->account_holder_id;
        $user_account_holder_id = auth()->user()->account_holder_id;
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

        if(isset($postings_A['success']) && isset($postings_B['success'])) {
            InvoiceJournalEvent::create([
                'journal_event_id' => $journal_event_id,
                'invoice_id' => $invoice_id
            ]);
            return true;
        }
    }

    public function program_refunds_for_monies_pending($program, $amount, $notes, $invoice_id )   {
        $program_account_holder_id = $program->account_holder_id;
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

        if(isset($postings_A['success']) && isset($postings_B['success'])) {
            InvoiceJournalEvent::create([
                'journal_event_id' => $journal_event_id,
                'invoice_id' => $invoice_id
            ]);
            return true;
        }
    }

    public function program_pays_for_points_transaction_fee($program, $amount, $notes, $invoice_id )   {
        return $this->program_pays_for('Program pays for points transaction fee', $program, $amount, $notes, $invoice_id);    
    }
    
    public function program_pays_for($payment_kind, $program, $amount, $notes, $invoice_id )  {
        $program_account_holder_id = $program->account_holder_id;
        $user_account_holder_id = auth()->user()->account_holder_id;
        $owner_account_holder_id = Owner::find(1)->account_holder_id;
        $currency_id = Currency::getIdByType(config('global.default_currency'), true);

        //Start transaction
        $journal_event_id = 0;

        $monies = MediumType::getIdByName('Monies', true);
        $asset = FinanceType::getIdByName('Asset', true);
        $journal_event_type_id = JournalEventType::getIdByType( $payment_kind );

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

        if(isset($postings['success'])) {
            InvoiceJournalEvent::create([
                'journal_event_id' => $journal_event_id,
                'invoice_id' => $invoice_id
            ]);
            return true;
        }
    }
}
