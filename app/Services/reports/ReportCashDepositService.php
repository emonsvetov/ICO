<?php

namespace App\Services\reports;

use App\Models\AccountType;
use App\Models\JournalEventType;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ReportCashDepositService extends ReportServiceAbstract
{

    /**
     * @inheritDoc
     */
    protected function getBaseSql(): Builder
    {
        return DB::table(function ($subQuery) {
            $subQuery->from('postings');
            $subQuery->join('accounts', 'accounts.id', '=', 'postings.account_id');
            $subQuery->join('programs', 'programs.account_holder_id', '=', 'accounts.account_holder_id');
            $subQuery->leftJoin('account_types', 'account_types.id', '=', 'accounts.account_type_id');
            $subQuery->leftJoin('journal_events', 'journal_events.id', '=', 'postings.journal_event_id');
            $subQuery->leftJoin('journal_event_types', 'journal_event_types.id', '=', 'journal_events.journal_event_type_id');
            $subQuery->leftJoin('event_xml_data', 'event_xml_data.id', '=', 'journal_events.event_xml_data_id');
            $subQuery->leftJoin('invoice_journal_event', 'invoice_journal_event.journal_event_id', '=', 'journal_events.id');
            $subQuery->leftJoin('invoices', 'invoices.id', '=', 'invoice_journal_event.invoice_id');
            $subQuery->leftJoin('users', 'users.account_holder_id', '=', 'journal_events.prime_account_holder_id');
            $subQuery->leftJoin('journal_events as reversals', 'journal_events.id', '=', 'reversals.parent_journal_event_id');
            $subQuery->leftJoin('programs as parent', 'parent.id', '=', 'programs.parent_id');

            $subQuery->addSelect([
                'accounts.account_holder_id',
                'programs.id',
                'programs.name',
                DB::raw("
                    getProgramRoot(`programs`.id) as `root_id`
                "),
                DB::raw("
                    (SELECT name FROM programs root_programs WHERE root_programs.id = getProgramRoot(`programs`.id)) as `root_name`
                "),
                'postings.id AS posting_id,',
                'postings.created_at AS date_paid',
                'postings.is_credit',
                DB::raw('concat(invoices.key, "-", invoices.seq) as invoice_number'),
                'invoices.id AS invoice_id',
                'journal_event_types.type AS journal_event_type',
                'journal_events.notes',
                DB::raw("
                (
                    SELECT
                        ( CAST(p_subselect.qty AS UNSIGNED)  * p_subselect.posting_amount) AS amount
                        #jet_subselect.`type`
                    FROM
                        `postings` p_subselect
                        INNER JOIN `journal_events` je_subselect on je_subselect.`id` = p_subselect.`journal_event_id`
                        INNER JOIN `journal_event_types` jet_subselect on jet_subselect.`id` = je_subselect.`journal_event_type_id`
                    WHERE
                        je_subselect.id = `postings`.`journal_event_id`
                        AND jet_subselect.type = 'Program pays for monies pending'
                    LIMIT 1
                ) as 'Funding Deposit'
                "),
                DB::raw("
                (
                    SELECT
                        ( CAST(p_subselect.qty AS UNSIGNED)  * p_subselect.posting_amount) AS amount
                        #jet_subselect.`type`
                    FROM
                        `postings` p_subselect
                        INNER JOIN `journal_events` je_subselect on je_subselect.`id` = p_subselect.`journal_event_id`
                        INNER JOIN `journal_event_types` jet_subselect on jet_subselect.`id` = je_subselect.`journal_event_type_id`
                    WHERE
                        je_subselect.id = `postings`.`journal_event_id`
                        AND jet_subselect.type = 'Program pays for deposit fee'
                    LIMIT 1
                ) as 'Deposit fee'
                "),
                DB::raw("
                (
                    SELECT
                        ( CAST(p_subselect.qty AS UNSIGNED)  * p_subselect.posting_amount) AS amount
                        #jet_subselect.`type`
                    FROM
                        `postings` p_subselect
                        INNER JOIN `journal_events` je_subselect on je_subselect.`id` = p_subselect.`journal_event_id`
                        INNER JOIN `journal_event_types` jet_subselect on jet_subselect.`id` = je_subselect.`journal_event_type_id`
                    WHERE
                        je_subselect.id = `postings`.`journal_event_id`
                        AND jet_subselect.type = 'Program pays for convenience fee'
                    LIMIT 1
                ) as 'Credit Card Convenience Fee'
                "),
            ]);
//            $subQuery->where('account_types.name', '=', AccountType::ACCOUNT_TYPE_MONIES_DUE_TO_OWNER);
            $subQuery->whereIn('journal_event_types.type', [
                JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_MONIES_PENDING,
                JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_DEPOSIT_FEE,
                JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_PAYS_FOR_CONVENIENCE_FEE,
            ]);
//        $query->whereIn('programs.account_holder_id', $this->params[self::PROGRAMS]);
            $subQuery->whereNull('reversals.id');
        }, 'subQuery')
            ->select(
                DB::raw("
                CASE
                    WHEN `root_name` = name THEN ''
                    ELSE `root_name`
                END as 'root_name',
                id as 'program_id',
                name as 'program_name',
                invoice_id,
                invoice_number,
                max(`date_paid`) as 'date_of_deposit',
                (
                    CASE
                        WHEN max(`Funding Deposit`) IS NULL THEN 0
                        ELSE max(`Funding Deposit`)
                    END +
                    CASE
                        WHEN max(`Deposit fee`) IS NULL THEN 0
                        ELSE max(`Deposit fee`)
                    END +
                    CASE
                        WHEN max(`Credit Card Convenience Fee`) IS NULL THEN 0
                        ELSE max(`Credit Card Convenience Fee`)
                    END
                ) as 'total_amount_received',
                max(`Funding Deposit`) as 'funding_deposit',
                max(`Deposit fee`) as 'deposit_fee',
                max(`Credit Card Convenience Fee`) as 'credit_card_convenience_fee',
                max(`notes`)
                ")
            )->groupBy(['id', 'invoice_id']);
    }

    /**
     * @inheritDoc
     */
    protected function setWhereFilters(Builder $query): Builder
    {
////        $query->whereIn('programs.account_holder_id', $this->params[self::PROGRAMS]);
        return $query;
    }

    /**
     * @inheritDoc
     */
    protected function setGroupBy(Builder $query): Builder
    {
        return $query;
    }

    /**
     * @inheritDoc
     */
    protected function setOrderBy(Builder $query): Builder
    {
        return $query;
    }

    public function getCsvHeaders(): array
    {
        return [
            [
                'label' => 'Root Program',
                'key' => 'root_name'
            ],
            [
                'label' => 'Program ID',
                'key' => 'program_id'
            ],
            [
                'label' => 'Program Name',
                'key' => 'program_name'
            ],
            [
                'label' => 'Invoice Number',
                'key' => 'invoice_number'
            ],
            [
                'label' => 'Date of Deposit',
                'key' => 'date_of_deposit'
            ],
            [
                'label' => 'Total Amount received',
                'key' => 'total_amount_received'
            ],
            [
                'label' => 'Funding Deposit',
                'key' => 'funding_deposit'
            ],
            [
                'label' => 'Deposit fee',
                'key' => 'deposit_fee'
            ],
            [
                'label' => 'Credit Card Convenience Fee',
                'key' => 'credit_card_convenience_fee'
            ],
            [
                'label' => 'Notes',
                'key' => 'notes'
            ],
        ];
    }

}
