<?php
namespace App\Services\v2migrate;

use App\Models\Domain;
use App\Models\Invoice;
use App\Models\InvoiceJournalEvent;
use App\Models\InvoiceType;
use App\Models\JournalEvent;
use App\Models\Program;
use App\Models\User;
use App\Services\ProgramService;
use Exception;

class MigrateInvoiceService extends MigrationService
{

    private ProgramService $programService;

    public $countUpdatedInvoices = 0;
    public $countCreateInvoices = 0;
    public $v2InvoiceTypes = [];
    public $v3InvoiceTypes = [];

    public function __construct(ProgramService $programService)
    {
        parent::__construct();
        $this->programService = $programService;
    }

    /**
     * Run migrate invoices.
     *
     * @param $v2AccountHolderID
     * @return array
     * @throws Exception
     */
    public function migrate($v2AccountHolderID)
    {
        $result = [
            'success' => FALSE,
            'info' => '',
        ];

        $v3Program = Program::where('v2_account_holder_id', $v2AccountHolderID)->first();
        $v3AccountHolderID = $v3Program->account_holder_id ?? NULL;

        // Checking.
        if (empty($v3AccountHolderID)) {
            throw new Exception("v3 program with ID: " . $v2AccountHolderID . " not found.");
        }
        $this->v2InvoiceTypes = $this->getV2InvoiceTypes();
        $this->v3InvoiceTypes = InvoiceType::all()->pluck('name', 'id')->toArray();

        $this->getSubPrograms($v3Program);

        try {
            $result['success'] = TRUE;
            $result['info'] = "update $this->countUpdatedInvoices items, create $this->countCreateInvoices items";
        } catch (\Exception $exception) {
            throw new Exception("Sync program settings is failed.");
        }

        return $result;
    }

    /**
     * Sync invoices.
     */
    public function getSubPrograms($v3Program)
    {
        $programs = $this->programService->getHierarchyByProgramId($organization = FALSE, $v3Program->id)->toArray();
        $subPrograms = $programs[0]["children"] ?? FALSE;

        $v3SubProgram = Program::find($v3Program->id);
        $v2AccountHolderID = $v3SubProgram->v2_account_holder_id ?? FALSE;

        if ($v2AccountHolderID) {
            $this->syncInvoices($v2AccountHolderID);
        }

        if (!empty($subPrograms)) {
            foreach ($subPrograms as $subProgram) {
                $this->getSubPrograms($subProgram);
            }
        }
    }

    /**
     * Get invoice types.
     *
     * @return array
     */
    public function getV2InvoiceTypes()
    {
        $result = [];
        $v2Sql = "SELECT * from invoice_types";

        $v2Invoices = $this->v2db->select($v2Sql);
        foreach ($v2Invoices as $v2Invoice) {
            $result[$v2Invoice->id] = $v2Invoice->type;
        }

        return $result;
    }

    /**
     * Get v3 invoice type ID.
     */
    public function getV3InvoiceTypeID($v2InvoiceTypeID)
    {
        $v2InvoiceTypeName = $this->v2InvoiceTypes[$v2InvoiceTypeID];
        $v3InvoiceTypeID = array_search($v2InvoiceTypeName, $this->v3InvoiceTypes);

        if (!$v3InvoiceTypeID) {
            $v3InvoiceType = InvoiceType::create([
                'name' => $v2InvoiceTypeName,
                'description' => '',
            ]);
            $v3InvoiceTypeID = $v3InvoiceType->id;
            $this->v3InvoiceTypes = InvoiceType::all()->pluck('name', 'id')->toArray();
        }

        return $v3InvoiceTypeID;
    }

    /**
     * Sync invoices.
     */
    public function syncInvoices($v2AccountHolderID)
    {
        $v2Invoices = $this->getV2Invoices($v2AccountHolderID);

        foreach ($v2Invoices as $v2Invoice) {

            $v3Program = Program::where('v2_account_holder_id', $v2AccountHolderID)->first();
            $v3AccountHolderID = $v3Program->account_holder_id ?? NULL;

            // Checking if v3 program is exists.
            if (empty($v3AccountHolderID)) {
                throw new Exception("v3 program with ID: " . $v2AccountHolderID . " not found.");
            }

            $v3InvoiceData = [
                'program_id' => $v3Program->id,
                'key' => $v2Invoice->key,
                'seq' => $v2Invoice->seq,
                'invoice_type_id' => $this->getV3InvoiceTypeID($v2Invoice->invoice_type_id),
                'payment_method_id' => $v2Invoice->payment_method_id,
                'date_begin' => $v2Invoice->date_begin,
                'date_end' => $v2Invoice->date_end,
                'date_due' => $v2Invoice->date_due,
                'amount' => $v2Invoice->invoice_amount,
                'participants' => $v2Invoice->participants,
                'new_participants' => $v2Invoice->new_participants,
                'managers' => $v2Invoice->managers,
                'created_at' => $v2Invoice->created,
                'v2_invoice_id' => $v2Invoice->id,
            ];

            $v3Invoice = Invoice::where('v2_invoice_id', $v2Invoice->id)->first();
            if (blank($v3Invoice)) {
                $v3Invoice = Invoice::create($v3InvoiceData);

                $v2JournalEvents = $this->getV2JournalEvent($v2Invoice);
                foreach ($v2JournalEvents as $v2JournalEvent) {
                    $journalEventsID = $this->addToJournalEvents($v3Invoice, $v2Invoice, $v2JournalEvent);
                    $this->addToInvoiceJournalEvent($v3Invoice, $journalEventsID);
                }

                $this->countCreateInvoices++;
            }
            else {
                $v3Invoice->update($v3InvoiceData);
                $this->countUpdatedInvoices++;
            }
        }
    }

    /**
     * Add record to invoice_journal_event.
     *
     * @param $v3Invoice
     * @param $journalEventsID
     */
    public function addToInvoiceJournalEvent($v3Invoice, $journalEventsID)
    {
        InvoiceJournalEvent::create([
            'journal_event_id' => $journalEventsID,
            'invoice_id' => $v3Invoice->id,
        ]);
    }

    /**
     * Get v2 journal event.
     *
     * @param $v2Invoice
     */
    public function getV2JournalEvent($v2Invoice)
    {
        $v2Sql = "
            SELECT je.* FROM journal_events je
            LEFT JOIN invoice_journal_events ije ON je.id = ije.journal_event_id
            WHERE ije.invoice_id = {$v2Invoice->id}";

        return $this->v2db->select($v2Sql);
    }

    /**
     * Add record to journal events.
     * @throws Exception
     */
    public function addToJournalEvents($v3Invoice, $v2Invoice, $v2JournalEvent)
    {
        $v2UserID = $v2JournalEvent->prime_account_holder_id;

        $journalEvent = JournalEvent::create([
            'prime_account_holder_id' => $v2UserID ? $this->getV3UserID($v2UserID) : 0,
            'journal_event_type_id' => $v2JournalEvent->journal_event_type_id,
            'notes' => $v2JournalEvent->notes,
            'event_xml_data_id' => NULL, //TODO?
            'invoice_id' => NULL,
            'is_read' => $v2JournalEvent->is_read,
            'parent_journal_event_id' => 0,
            'v2_journal_event_id' => $v2JournalEvent->id,
            'v2_prime_account_holder_id' => $v2JournalEvent->prime_account_holder_id,
            'v2_parent_journal_event_id' => $v2JournalEvent->parent_journal_event_id,
        ]);

        return $journalEvent->id;
    }

    /**
     * Get v3 user ID.
     *
     * @param $v2UserID
     * @return mixed
     * @throws Exception
     */
    public function getV3UserID($v2UserID)
    {
        $v2Sql = "SELECT u.* FROM users u WHERE u.account_holder_id = {$v2UserID} LIMIT 1";
        $result = $this->v2db->select($v2Sql);
        $v2User = reset($result);

        $v3UserID = $v2User->v3_user_id ?? FALSE;
        if (!$v3UserID) {
            $v3User = User::where('email', $v2User->email)->first();
            $v3UserID = $v3User->id ?? FALSE;
        }

        if (!$v3UserID) {
            throw new Exception("Sync invoices is failed. User for v3 not found. The user on v2 has an ID = {$v2UserID} and email = {$v2User->email}");
        }

        return $v3UserID;
    }

    /**
     * v2 read_list_invoices_by_program.
     *
     * @param $v2AccountHolderID
     * @return array
     */
    public function getV2Invoices($v2AccountHolderID)
    {
        $v2Sql = "
            SELECT
                i.*,
                concat(i.key, '-', i.seq) as invoice_number
            FROM
                invoices i
                join invoice_types t on (i.invoice_type_id = t.id)
			WHERE program_account_holder_id = {$v2AccountHolderID}
            ORDER BY
                i.`id` DESC
        ;
        ";

        return $this->v2db->select($v2Sql);
    }

}
