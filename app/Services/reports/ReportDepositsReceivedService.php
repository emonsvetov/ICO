<?php

namespace App\Services\reports;

use App\Models\AccountType;
use App\Models\JournalEventType;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ReportDepositsReceivedService extends ReportServiceAbstract
{

    /**
     * @inheritDoc
     */
    protected function getBaseQuery(): string
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

        $journalEventTypes = 'JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_MONIES_PENDING';
        $accountTypeName = 'Monies Due to Owner';

        $select = "$ACCOUNTS.account_holder_id,
        $PROGRAMS.name,
        CONCAT($USERS.last_name, ' ', $USERS.first_name) AS admin,
        $POSTINGS.id AS posting_id,
        (CAST($POSTINGS.qty AS UNSIGNED) * $POSTINGS.posting_amount) AS amount,
        $POSTINGS.created_at AS date_paid,
        $POSTINGS.is_credit,
        CONCAT($INVOICES_TBL.key, '-', $INVOICES_TBL.seq) AS invoice_number,
        $INVOICES_TBL.id AS invoice_id,
        $JOURNAL_EVENT_TYPES.type AS journal_event_type,
        $JOURNAL_EVENTS.notes";

        $orderBy = "ORDER BY date_paid DESC, $ACCOUNTS.account_holder_id, invoice_number";

        return "SELECT $select
            FROM $POSTINGS
            INNER JOIN $ACCOUNTS ON $ACCOUNTS.id = $POSTINGS.account_id
            INNER JOIN $PROGRAMS ON $PROGRAMS.account_holder_id = $ACCOUNTS.account_holder_id
            INNER JOIN $ACCOUNT_TYPES ON $ACCOUNT_TYPES.id = $ACCOUNTS.account_type_id
            INNER JOIN $JOURNAL_EVENTS ON $JOURNAL_EVENTS.id = $POSTINGS.journal_event_id
            INNER JOIN $JOURNAL_EVENT_TYPES ON $JOURNAL_EVENT_TYPES.id = $JOURNAL_EVENTS.journal_event_type_id
            LEFT JOIN $EVENT_XML_DATA ON $JOURNAL_EVENTS.event_xml_data_id = $EVENT_XML_DATA.id
            LEFT JOIN $INVOICE_JOURNAL_EVENTS ON $INVOICE_JOURNAL_EVENTS.journal_event_id = $JOURNAL_EVENTS.id
            LEFT JOIN $INVOICES_TBL ON $INVOICES_TBL.id = $INVOICE_JOURNAL_EVENTS.invoice_id
            LEFT JOIN $USERS ON $USERS.account_holder_id = $JOURNAL_EVENTS.prime_account_holder_id
            LEFT JOIN journal_events reversals ON ($JOURNAL_EVENTS.id = reversals.parent_journal_event_id)
            WHERE
                $JOURNAL_EVENT_TYPES.type IN ('$journalEventTypes')
                AND $ACCOUNT_TYPES.name = '$accountTypeName'
                AND reversals.id IS NULL
            $orderBy";
    }


    /**
     * @inheritDoc
     */
    public function getCsvHeaders(): array
    {
        return [
            [
                'label' => 'Account Holder ID',
                'key' => 'account_holder_id',
            ],
            [
                'label' => 'Program Name',
                'key' => 'name',
            ],
            [
                'label' => 'Administrator',
                'key' => 'admin',
            ],
            [
                'label' => 'Posting ID',
                'key' => 'posting_id',
            ],
            [
                'label' => 'Amount',
                'key' => 'amount',
            ],
            [
                'label' => 'Date Paid',
                'key' => 'date_paid',
            ],
            [
                'label' => 'Credit Indicator',
                'key' => 'is_credit',
            ],
            [
                'label' => 'Invoice Number',
                'key' => 'invoice_number',
            ],
            [
                'label' => 'Invoice ID',
                'key' => 'invoice_id',
            ],
            [
                'label' => 'Journal Event Type',
                'key' => 'journal_event_type',
            ],
            [
                'label' => 'Journal Event Notes',
                'key' => 'notes',
            ],
        ];
    }
}
