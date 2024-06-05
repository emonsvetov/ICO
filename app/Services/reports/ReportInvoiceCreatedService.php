<?php

namespace App\Services\reports;

use App\Models\AccountType;
use App\Models\JournalEventType;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ReportInvoiceCreatedService extends ReportServiceAbstract
{
    protected bool $isArrangeByAccountHolderId = false;
    /**
     * @inheritDoc
     */
    protected function getBaseQuery(): Builder
    {
        $query = DB::table('postings');

        $journalEventTypes = [
            JournalEventType::JOURNAL_EVENT_TYPES_CHARGE_MONIES_PENDING,
            JournalEventType::JOURNAL_EVENT_TYPES_CHARGE_DEPOSIT_FEE
        ];

        $select = "accounts.account_holder_id,
        programs.name,
        IFNULL(programs.v2_account_holder_id, programs.account_holder_id) as program_id,
        CONCAT(users.last_name, ' ', users.first_name) AS admin,
        postings.id AS posting_id,
        (CAST(postings.qty AS UNSIGNED) * postings.posting_amount) AS amount,
        postings.created_at AS date_paid,
        postings.is_credit,
        CONCAT(invoices.key, '-', invoices.seq) AS invoice_number,
        invoices.id AS invoice_id,
        journal_event_types.type AS journal_event_type,
        journal_events.notes";
        $query->join('accounts', "accounts.id", "=", "postings.account_id")
            ->join('programs', "programs.account_holder_id", "=", "accounts.account_holder_id")
            ->join('account_types', "account_types.id", "=", "accounts.account_type_id")
            ->join('journal_events', "journal_events.id", "=", "postings.journal_event_id")
            ->join('journal_event_types', "journal_event_types.id", "=", "journal_events.journal_event_type_id")
            ->leftJoin('event_xml_data', "journal_events.event_xml_data_id", "=", "event_xml_data.id")
            ->leftJoin('invoice_journal_event', "invoice_journal_event.journal_event_id", "=", "journal_events.id")
            ->leftJoin('invoices', "invoices.id", "=", "invoice_journal_event.invoice_id")
            ->leftJoin('users', "users.account_holder_id", "=", "journal_events.prime_account_holder_id")
            ->whereIn("journal_event_types.type", $journalEventTypes)
            ->where("postings.is_credit" ,'=', 0)
            ->where("account_types.name" ,'=', AccountType::ACCOUNT_TYPE_MONIES_DUE_TO_OWNER)
            ->selectRaw($select);

        $query = $query->addSelect([
            DB::raw("
                getProgramRoot(`programs`.id) as `root_id`
            "),
            DB::raw("
                (SELECT name FROM programs root_programs WHERE root_programs.id = getProgramRoot(`programs`.id)) as `root_name`
            "),
        ]);

        return $query;
    }

    protected function prepareCalcResults( $data ){
        $results = [];
        foreach ($data as $row) {
           $invoiceId = (int)$row->invoice_id;

           if(!isset($results[$invoiceId])){
               $results[$invoiceId] = [
                   'account_holder_id' => $row->account_holder_id,
                   'name' => $row->name,
                   'root_name' => $row->root_name,
                   'program_id' => $row->program_id,
                   'admin' => $row->admin,
                   'posting_id' => $row->posting_id,
                   'amount' => 0,
                   'deposit_fee' => 0,
                   'date_paid' => $row->date_paid,
                   'is_credit' => $row->is_credit,
                   'invoice_number' => $row->invoice_number,
                   'invoice_id' => $row->invoice_id,
                   'notes' => $row->notes
               ];
           }

           if( $row->journal_event_type == JournalEventType::JOURNAL_EVENT_TYPES_CHARGE_MONIES_PENDING){
               $results[$invoiceId]['amount'] = $row->amount;
           }elseif($row->journal_event_type == JournalEventType::JOURNAL_EVENT_TYPES_CHARGE_DEPOSIT_FEE){
               $results[$invoiceId]['deposit_fee'] = $row->amount;
           }
        }
        return array_values($results);
    }

    protected function setOrderBy(Builder $query): Builder
    {
        $query->orderByDesc("postings.created_at")
        ->orderBy("accounts.account_holder_id")
        ->orderBy("invoices.id");
        return $query;
    }

    protected function setGroupBy(Builder $query): Builder
    {
        //$query->groupBy("invoices.id");
        return $query;
    }

    /**
     * @inheritDoc
     */
    protected function setWhereFilters(Builder $query): Builder
    {
        $from = Carbon::parse($this->params[self::DATE_BEGIN])->addDays(1)->toDateTimeString();
        $to = Carbon::parse($this->params[self::DATE_END])->addDays(2)->toDateTimeString();
        $query->whereBetween('postings.created_at', [$from, $to]);
        if( $this->params[self::PROGRAMS] ) {
            $query->whereIn('programs.id', $this->params[self::PROGRAMS]);
        }
        if( isset($this->params['programId']) &&  $this->params['programId']) {
            $query->where('programs.id', '=', $this->params['programId']);
        }
        $invoiceNumber = request('invoiceNumber');
        if( $invoiceNumber ) {
            $parts = explode('-', $invoiceNumber);
            $query->where('invoices.key', '=', $parts[0])->where('seq', '=', $parts[1]);
        }
        return $query;
    }

    public function getCsvHeaders(): array
    {
        return [
            ['label' => 'Account Holder ID', 'key' => 'account_holder_id'],
            ['label' => 'Program Name', 'key' => 'name'],
            ['label' => 'Administrator', 'key' => 'admin'],
            ['label' => 'Posting ID', 'key' => 'posting_id'],
            ['label' => 'Amount', 'key' => 'amount'],
            ['label' => 'Date Paid', 'key' => 'date_paid'],
            ['label' => 'Credit Indicator', 'key' => 'is_credit'],
            ['label' => 'Invoice Number', 'key' => 'invoice_number'],
            ['label' => 'Invoice ID', 'key' => 'invoice_id'],
            ['label' => 'Journal Event Type', 'key' => 'journal_event_type'],
            ['label' => 'Journal Event Notes', 'key' => 'notes'],
        ];
    }
}
