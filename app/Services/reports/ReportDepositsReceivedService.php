<?php

namespace App\Services\reports;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ReportDepositsReceivedService extends ReportServiceAbstract
{
    /**
     * @inheritDoc
     */
    protected function getBaseQuery(): array
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

        $journalEventTypes = 'Program pays for monies pending';

        $select = "$ACCOUNTS.account_holder_id,
            $PROGRAMS.name,
            CONCAT($USERS.last_name, ' ', $USERS.first_name) AS admin,
            $POSTINGS.id AS posting_id,
            (CAST($POSTINGS.qty AS UNSIGNED) * $POSTINGS.posting_amount) AS amount,
            $POSTINGS.created_at AS date_paid,
            $POSTINGS.is_credit,
            CONCAT($INVOICES_TBL.key, '-', $INVOICES_TBL.seq) AS invoice_number,
            $INVOICES_TBL.id AS invoice_id,
            '$journalEventTypes' AS journal_event_type,
            $JOURNAL_EVENTS.notes";

        $query = DB::table($POSTINGS)
            ->join($ACCOUNTS, "$ACCOUNTS.id", "=", "$POSTINGS.account_id")
            ->join($PROGRAMS, "$PROGRAMS.account_holder_id", "=", "$ACCOUNTS.account_holder_id")
            ->join($ACCOUNT_TYPES, "$ACCOUNT_TYPES.id", "=", "$ACCOUNTS.account_type_id")
            ->join($JOURNAL_EVENTS, "$JOURNAL_EVENTS.id", "=", "$POSTINGS.journal_event_id")
            ->join($JOURNAL_EVENT_TYPES, "$JOURNAL_EVENT_TYPES.id", "=", "$JOURNAL_EVENTS.journal_event_type_id")
            ->leftJoin($EVENT_XML_DATA, "$JOURNAL_EVENTS.event_xml_data_id", "=", "$EVENT_XML_DATA.id")
            ->leftJoin($INVOICE_JOURNAL_EVENTS, "$INVOICE_JOURNAL_EVENTS.journal_event_id", "=", "$JOURNAL_EVENTS.id")
            ->leftJoin($INVOICES_TBL, "$INVOICES_TBL.id", "=", "$INVOICE_JOURNAL_EVENTS.invoice_id")
            ->leftJoin($USERS, "$USERS.account_holder_id", "=", "$JOURNAL_EVENTS.prime_account_holder_id")
            ->leftJoin('journal_events AS reversals', "$JOURNAL_EVENTS.id", "=", "reversals.parent_journal_event_id")
            ->where("$JOURNAL_EVENT_TYPES.type", '=', $journalEventTypes)
            ->whereNull('reversals.id')
            ->selectRaw($select);

        $result = $query->orderByDesc("$POSTINGS.created_at")
            ->orderBy("$ACCOUNTS.account_holder_id")
            ->orderBy("invoice_number")
            ->get();

        $table = [];

        foreach ($result as $row) {
            $merchantId = (int)$row->account_holder_id;

            $table[$merchantId] = [
                'account_holder_id' => $row->account_holder_id,
                'name' => $row->name,
                'admin' => $row->admin,
                'posting_id' => $row->posting_id,
                'amount' => $row->amount,
                'date_paid' => $row->date_paid,
                'is_credit' => $row->is_credit,
                'invoice_number' => $row->invoice_number,
                'invoice_id' => $row->invoice_id,
                'journal_event_type' => $journalEventTypes,
                'notes' => $row->notes,
            ];
        }

        return $table;
    }

    /**
     * @inheritDoc
     */
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
