<?php
namespace App\Services;

use App\Services\Program\Traits\ProgramPaymentReversalTrait;
use App\Services\Program\Traits\ProgramPaymentTrait;
use App\Models\JournalEventType;
use App\Models\Posting;
use App\Models\Invoice;

class ProgramPaymentService
{
    use ProgramPaymentTrait;
    use ProgramPaymentReversalTrait;

    public $program;
    public $program_account_holder_id;
    public $user_account_holder_id;
    public $invoiceService;

    public function __construct(
        InvoiceService $invoiceService
    ) {
        $this->invoiceService = $invoiceService;
    }

    public function getPayments($program)   {
        $pays_for_points = request()->get('pays_for_points', false);
        if( $pays_for_points ) {
            return $this->read_list_program_pays_for($program, 'date_paid', 'DESC');
        }
        $invoices = $this->invoiceService->index($program, false);
        
        return [
            'payment_kinds' => Invoice::PROGRAM_PAYMENT_KINDS,
            'invoices' => $invoices,
            'invoice_id' => request()->get('invoice_id', null),
        ];
    }

    public function read_list_program_pays_for($program, $orderBy = 'invoice_id', $orderDirection = 'ASC')    {

        $sortby = request()->get('sortby', $orderBy);
        $direction = request()->get('direction', $orderDirection);
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
}
