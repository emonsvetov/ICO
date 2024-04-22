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
        $POSTINGS = 'postings';
        $ACCOUNTS = 'accounts';
        $PROGRAMS = 'programs';
        $ACCOUNT_TYPES = 'account_types';
        $JOURNAL_EVENTS = 'journal_events';
        $JOURNAL_EVENT_TYPES = 'journal_event_types';
        $EVENT_XML_DATA = 'event_xml_data';
        $INVOICE_JOURNAL_EVENTS = 'invoice_journal_event';
        $INVOICES_TBL = 'invoices';
        $USERS = 'users';

        $query = DB::table($POSTINGS);

        $select = "$ACCOUNTS.account_holder_id,
        $PROGRAMS.name,
        IFNULL($PROGRAMS.v2_account_holder_id, $PROGRAMS.account_holder_id) as program_id,
        CONCAT($USERS.last_name, ' ', $USERS.first_name) AS admin,
        $POSTINGS.id AS posting_id,
        (CAST($POSTINGS.qty AS UNSIGNED) * $POSTINGS.posting_amount) AS amount,
        $POSTINGS.created_at AS date_paid,
        $POSTINGS.is_credit,
        CONCAT($INVOICES_TBL.key, '-', $INVOICES_TBL.seq) AS invoice_number,
        $INVOICES_TBL.id AS invoice_id,
        $JOURNAL_EVENT_TYPES.type AS journal_event_type,
        $JOURNAL_EVENTS.notes";
        $query->join($ACCOUNTS, "$ACCOUNTS.id", "=", "$POSTINGS.account_id")
            ->join($PROGRAMS, "$PROGRAMS.account_holder_id", "=", "$ACCOUNTS.account_holder_id")
            ->join($ACCOUNT_TYPES, "$ACCOUNT_TYPES.id", "=", "$ACCOUNTS.account_type_id")
            ->join($JOURNAL_EVENTS, "$JOURNAL_EVENTS.id", "=", "$POSTINGS.journal_event_id")
            ->join($JOURNAL_EVENT_TYPES, "$JOURNAL_EVENT_TYPES.id", "=", "$JOURNAL_EVENTS.journal_event_type_id")
            ->leftJoin($EVENT_XML_DATA, "$JOURNAL_EVENTS.event_xml_data_id", "=", "$EVENT_XML_DATA.id")
            ->leftJoin($INVOICE_JOURNAL_EVENTS, "$INVOICE_JOURNAL_EVENTS.journal_event_id", "=", "$JOURNAL_EVENTS.id")
            ->leftJoin($INVOICES_TBL, "$INVOICES_TBL.id", "=", "$INVOICE_JOURNAL_EVENTS.invoice_id")
            ->leftJoin($USERS, "$USERS.account_holder_id", "=", "$JOURNAL_EVENTS.prime_account_holder_id")
            ->whereIn("$JOURNAL_EVENT_TYPES.type", [
                JournalEventType::JOURNAL_EVENT_TYPES_CHARGE_MONIES_PENDING,
                JournalEventType::JOURNAL_EVENT_TYPES_CHARGE_DEPOSIT_FEE
            ])
            ->where("$POSTINGS.is_credit" ,'=', 0)
            ->where("$ACCOUNT_TYPES.name" ,'=', AccountType::ACCOUNT_TYPE_MONIES_DUE_TO_OWNER)
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

        // $result = $query->get();

        // $table = [];

        // foreach ($result as $row) {
        //     $accountHolderId = (int)$row->account_holder_id;

        //     $table[$accountHolderId] = [
        //         'account_holder_id' => $row->account_holder_id,
        //         'name' => $row->name,
        //         'admin' => $row->admin,
        //         'posting_id' => $row->posting_id,
        //         'amount' => $row->amount,
        //         'date_paid' => $row->date_paid,
        //         'is_credit' => $row->is_credit,
        //         'invoice_number' => $row->invoice_number,
        //         'invoice_id' => $row->invoice_id,
        //         'journal_event_type' => $journalEventTypes,
        //         'notes' => $row->notes,
        //     ];
        // }

        // return $table;
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
        $query->groupBy("invoices.id");
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
