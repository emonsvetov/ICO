<?php
namespace App\Services\v2migrate;

use Illuminate\Support\Facades\DB;
use RuntimeException;
use Exception;

use App\Models\Invoice;

class MigrateInvoiceJournalEventsService extends MigrationService
{

    public $offset = 0;
    public $limit = 1000;
    public $iteration = 0;
    public $count = 0;
    public bool $printSql = true;

    public function __construct()
    {
        parent::__construct();
    }

    public function migrate() {
        $this->v2db->statement("SET SQL_MODE=''");
        $this->migrateInvoiceJournalEvents(); //This will sync only
        printf("%d `invoice_journal_events` relations created in %d iterations\n", $this->count, $this->iteration);
    }

    public function migrateInvoiceJournalEventsByInvoice( $v2Invoice, $v3Invoice )
    {
        $v2_invoice_id = $v2Invoice->id;

        $sql = sprintf("SELECT je.*, je.v3_journal_event_id FROM `invoice_journal_events` ije JOIN journal_events je ON je.id=ije.journal_event_id JOIN invoices inv ON inv.id = ije.invoice_id WHERE inv.id=%d", $v2_invoice_id);
        $v2InvoiceJournalEvents = $this->v2db->select($sql);
        $countV2InvoiceJournalEvents = sizeof($v2InvoiceJournalEvents);
        if( $countV2InvoiceJournalEvents > 0 )   {
            $v3jeToSync = [];
            foreach( $v2InvoiceJournalEvents as $v2InvoiceJournalEvent) {
                if( !$v2InvoiceJournalEvent->v3_journal_event_id )  {
                    $v3JournalEvent = (new \App\Services\v2migrate\MigrateJournalEventsService)->migrateSingleJournalEvent($v2InvoiceJournalEvent);
                    $v3jeToSync[] = $v3JournalEvent->id;
                }   else {
                    $v3jeToSync[] = $v2InvoiceJournalEvent->v3_journal_event_id;
                }
            }
            if( $v3jeToSync ) {
                $v3Invoice->journal_events()->sync($v3jeToSync);
            }
            // printf("Found %d InvoiceJournalEvents in iteration:%d\n", $countV2InvoiceJournalEvents, $this->iteration);
            // foreach( $v2InvoiceJournalEvents as $v2InvoiceJournalEvent) {
            //     // print_r($v2InvoiceJournalEvent);
            //     if( !isset($cacheInvoiceJournalEvents[$v2InvoiceJournalEvent->v3_invoice_id]) ) {
            //         $cacheInvoiceJournalEvents[$v2InvoiceJournalEvent->v3_invoice_id] = [];
            //     }
            //     $cacheInvoiceJournalEvents[$v2InvoiceJournalEvent->v3_invoice_id][] = $v2InvoiceJournalEvent->v3_journal_event_id;
            // }
            // // pr($cacheInvoiceJournalEvents);
            // $v2InvoiceIds = array_keys($cacheInvoiceJournalEvents);
            // $v3Invoices = Invoice::whereIn('id', $v2InvoiceIds)->get();
            // $this->iteration++;
            // if( $v3Invoices->isNotEmpty() ) {
            //     // pr($v3Invoices->toArray());
            //     foreach( $v3Invoices as $v3Invoice) {
            //         $v3Invoice->journal_events()->sync($cacheInvoiceJournalEvents[$v3Invoice->id]);
            //         printf("%d journal_events synced to v3invoice:%d in iteration:%d\n", count($cacheInvoiceJournalEvents[$v3Invoice->id]), $v3Invoice->id, $this->iteration);
            //     }
            // }
        }
    }

    public function migrateInvoiceJournalEvents()
    {
        // DB::beginTransaction();
        // $this->v2db->beginTransaction();
        try{
            $cacheInvoiceJournalEvents = [];
            $sql = sprintf("SELECT * FROM `invoice_journal_events` ije JOIN journal_events je ON je.id=ije.journal_event_id JOIN invoices inv ON inv.id = ije.invoice_id WHERE je.v3_journal_event_id IS NOT NULL AND inv.v3_invoice_id IS NOT NULL LIMIT %d, %d", $this->offset, $this->limit);
            $v2InvoiceJournalEvents = $this->v2db->select($sql);
            if( $countV2InvoiceJournalEvents = sizeof($v2InvoiceJournalEvents) > 0 )   {
                printf("Found %d InvoiceJournalEvents in iteration:%d\n", $countV2InvoiceJournalEvents, $this->iteration);
                foreach( $v2InvoiceJournalEvents as $v2InvoiceJournalEvent) {
                    // print_r($v2InvoiceJournalEvent);
                    if( !isset($cacheInvoiceJournalEvents[$v2InvoiceJournalEvent->v3_invoice_id]) ) {
                        $cacheInvoiceJournalEvents[$v2InvoiceJournalEvent->v3_invoice_id] = [];
                    }
                    $cacheInvoiceJournalEvents[$v2InvoiceJournalEvent->v3_invoice_id][] = $v2InvoiceJournalEvent->v3_journal_event_id;
                }
                // pr($cacheInvoiceJournalEvents);
                $v2InvoiceIds = array_keys($cacheInvoiceJournalEvents);
                $v3Invoices = Invoice::whereIn('id', $v2InvoiceIds)->get();
                $this->iteration++;
                if( $v3Invoices->isNotEmpty() ) {
                    // pr($v3Invoices->toArray());
                    foreach( $v3Invoices as $v3Invoice) {
                        $v3Invoice->journal_events()->sync($cacheInvoiceJournalEvents[$v3Invoice->id]);
                        printf("%d journal_events synced to v3invoice:%d in iteration:%d\n", count($cacheInvoiceJournalEvents[$v3Invoice->id]), $v3Invoice->id, $this->iteration);
                    }
                }
            }
            // DB::commit();
            // $this->v2db->commit();
            if( $countV2InvoiceJournalEvents >= $this->limit) {
                if( $this->count >= 20 ) exit;
                $this->offset = $this->offset + $this->limit;
                $this->migrateInvoiceJournalEvents();
            }
        }   catch (Exception $e) {
            // DB::rollback();
            // $this->v2db->rollBack();
            printf("Could not import InvoiceJournalEvents for error:\"%s\" in iteration %d.\n", $e->getMessage(), $this->iteration);
        }
    }
}
