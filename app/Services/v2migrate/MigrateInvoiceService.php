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


    public $countUpdatedInvoices = 0;
    public $countCreateInvoices = 0;
    public $v2InvoiceTypes = [];
    public $v3InvoiceTypes = [];

    public function __construct()
    {
        parent::__construct();
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
        $v2AccountHolderID = $v3Program->v2_account_holder_id ?? FALSE;
        $subPrograms = $v3Program->children ?? [];

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

        $v3Program = Program::where('v2_account_holder_id', $v2AccountHolderID)->first();
        $v3AccountHolderID = $v3Program->account_holder_id ?? NULL;

        // Checking if v3 program is exists.
        if (empty($v3AccountHolderID)) {
            throw new Exception("v3 program with ID: " . $v2AccountHolderID . " not found.");
        }

        foreach ($v2Invoices as $v2Invoice) {

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
                    $journalEventsID = $this->getJournalEvent($v3Invoice, $v2Invoice, $v2JournalEvent);
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
     * Get v3 Journal Even
     *
     * @param $v3Invoice
     * @param $v2Invoice
     * @param $v2JournalEvent
     * @return mixed
     * @throws Exception
     */
    public function getJournalEvent($v3Invoice, $v2Invoice, $v2JournalEvent)
    {
        $journalEvent = JournalEvent::where('v2_journal_event_id', $v2JournalEvent->id)->first();
        if (blank($journalEvent)) {
            throw new Exception("v3 journal event not found. The v2 journal event ID {$v2JournalEvent->id}");
        }

        return $journalEvent->id;
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

}
