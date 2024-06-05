<?php

namespace App\Services\reports;

use App\Models\AccountType;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ReportAwardDetailService extends ReportServiceAbstract
{

    /**
     * @inheritDoc
     */
    protected function getBaseQuery(): Builder
    {
        $selectedPrograms = $this->params[self::PROGRAM_IDS];
        $query = DB::table('users');
        $query->join('accounts', 'accounts.account_holder_id', '=', 'users.account_holder_id');
        $query->join('account_types', 'account_types.id', '=', 'accounts.account_type_id');
        $query->join('postings', 'postings.account_id', '=', 'accounts.id');
        $query->join('journal_events', 'journal_events.id', '=', 'postings.journal_event_id');
        $query->join('journal_event_types', 'journal_event_types.id', '=', 'journal_events.journal_event_type_id');
        $query->join('postings as program_posting', 'program_posting.journal_event_id', '=', 'journal_events.id');
        $query->join('accounts as program_accounts', 'program_accounts.id', '=', 'program_posting.account_id');
        $query->join('account_types as program_account_types', function ($join) {
            $join->on('program_account_types.id', '=', 'program_accounts.account_type_id');
        });
        $query->join('programs', 'programs.account_holder_id', '=', 'program_accounts.account_holder_id');

        $query->leftJoin('event_xml_data', 'event_xml_data.id', '=', 'journal_events.event_xml_data_id');
        $query->leftJoin('users as awarder', 'awarder.account_holder_id', '=', 'event_xml_data.awarder_account_holder_id');
        $query->leftJoin('events', function ($join) use ($selectedPrograms) {
            $join->on('events.name', '=', 'event_xml_data.name')
                ->whereIn('events.program_id', $selectedPrograms);
        });
        $query->leftJoin('event_ledger_codes', 'event_ledger_codes.id', '=', 'events.ledger_code');
        $query->leftJoin('users as reclaim_user', 'reclaim_user.id', '=', 'journal_events.prime_account_holder_id');

        $query->addSelect([
            'programs.account_holder_id as program_id',
            'programs.name as program_name',
            'programs.external_id',
        ]);
        $query->addSelect([
            'users.account_holder_id as recipient_id',
            'users.first_name as recipient_first_name',
            'users.last_name as recipient_last_name',
            'users.email as recipient_email',
            'users.organization_id as recipient_organization_uid',
        ]);
        $query->addSelect(
            DB::raw("
                CASE
                    WHEN `postings`.`is_credit` = 1
                    THEN
                        if((`postings`.`posting_amount` is not null), `postings`.`posting_amount`, 0)
                    ELSE
                        (-1 * if((`postings`.`posting_amount` is not null), `postings`.`posting_amount`, 0))
                END as `dollar_value`
            "),
            DB::raw("
                CASE
                    WHEN `postings`.`is_credit` = 1
                    THEN
                        if((`postings`.`posting_amount` is not null), `postings`.`posting_amount`*`programs`.`factor_valuation`, 0)
                    ELSE
                        (-1 * if((`postings`.`posting_amount` is not null), `postings`.`posting_amount`*`programs`.`factor_valuation`, 0))
                END as `points`
            "),
            DB::raw("DATE_FORMAT(postings.created_at, '%m/%d/%Y') AS posting_timestamp"),
            "postings.id as posting_id"
        );
        $query->addSelect([
            DB::raw("IF(`event_xml_data`.`name` IS NULL,`journal_event_types`.`type`,`event_xml_data`.`name`) AS `event_name`"),
            'event_xml_data.id as event_xml_data_id',
            'event_xml_data.referrer',
            'event_xml_data.lease_number',
            'event_xml_data.notes',
            'event_xml_data.xml',
            'event_xml_data.event_template_id',
        ]);
        $query->addSelect([
            'event_xml_data.id as event_xml_data_id',
            'event_xml_data.referrer',
            'event_xml_data.lease_number',
            'event_xml_data.notes',
            'event_xml_data.xml',
            'event_xml_data.event_template_id',
        ]);
        $query->addSelect([
            'awarder.account_holder_id as awarder_id',
            DB::raw("
                CASE
                    WHEN (`journal_event_types`.`type` = 'Reclaim points' OR `journal_event_types`.`type` = 'Reclaim monies')
                    THEN `journal_events`.`notes`
                    ELSE `event_xml_data`.`notes`
                END AS `notes`,
                CASE
                    WHEN (`journal_event_types`.`type` = 'Reclaim points' OR `journal_event_types`.`type` = 'Reclaim monies')
                    THEN CONCAT(reclaim_user.`first_name`, ' ', `reclaim_user`.`last_name`)
                    ELSE CONCAT(`awarder`.`first_name`, ' ', `awarder`.`last_name`)
                END AS `awarder_full`,
                CASE
                    WHEN (`journal_event_types`.`type` = 'Reclaim points' OR `journal_event_types`.`type` = 'Reclaim monies')
                    THEN `reclaim_user`.`email`
                    ELSE ''
                END AS `awarder_email`
            ")
        ]);
        $query->addSelect([
            'accounts.id as account_id',
            'account_types.name as account_type',
        ]);
        $query->addSelect([
            'journal_events.id as journal_event_id',
            'journal_events.notes as journal_event_notes',
            'journal_event_types.type as journal_event_type',
        ]);
        $query->addSelect([
            'event_ledger_codes.ledger_code',
            DB::raw("
                IF(`event_xml_data`.`award_level_name` IS NULL,`journal_event_types`.`type`,`event_xml_data`.`award_level_name`) AS `award_level_name`
            ")
        ]);

        //throw new \Exception(print_r($query->toSql(),true));

        return $query;

    }

    protected function calc()
    {
        $this->table = [];
        $query = $this->getBaseQuery();
        $this->query = $query;
        $query = $this->setWhereFilters($query);
        $query = $this->setGroupBy($query);
        $query = $this->setOrderBy($query);
        $total = count($query->get()->toArray());
        $query = $this->setLimit($query);
        $this->table['data'] = $query->get()->toArray();
        $this->table['total'] = $total;
    }

    /**
     * @inheritDoc
     */
    protected function setWhereFilters(Builder $query): Builder
    {
        $query->whereIn('account_types.name', [
            AccountType::getTypePointsAwarded(),
            AccountType::getTypeMoniesAwarded(),
        ]);
        $query->whereIn('journal_event_types.type', [
            'Award points to recipient',
            'Award monies to recipient',
            'Reclaim points',
            'Reclaim monies'
        ]);

        $from = Carbon::parse($this->params[self::DATE_BEGIN])->addDays(0)->toDateTimeString();
        $to = Carbon::parse($this->params[self::DATE_END])->addDays(2)->toDateTimeString();
        $query->whereBetween('postings.created_at', [$from, $to]);
        $query->whereIn('programs.account_holder_id', $this->params[self::PROGRAMS]);
        if (isset($this->params[self::AWARD_LEVEL_NAMES]) && count($this->params[self::AWARD_LEVEL_NAMES]) > 0)
        {
            $query->whereIn('event_xml_data.award_level_name', $this->params[self::AWARD_LEVEL_NAMES]);
        }

        return $query;
    }

    /**
     * @inheritDoc
     */
    protected function setGroupBy(Builder $query): Builder
    {
        $query->groupBy('postings.id');
        return $query;
    }

    /**
     * @inheritDoc
     */
    protected function setOrderBy(Builder $query): Builder
    {
        $query->orderBy('postings.created_at', 'DESC');
        return $query;
    }

    public function getCsvHeaders(): array
    {
        if ($this->params[self::SERVER] === 'program'){
            return [
                [
                    'label' => 'Event',
                    'key' => 'event_name'
                ],
                [
                    'label' => 'GL Code',
                    'key' => 'ledger_code'
                ],
                [
                    'label' => 'Date',
                    'key' => 'posting_timestamp'
                ],
                [
                    'label' => 'First Name',
                    'key' => 'recipient_first_name'
                ],
                [
                    'label' => 'Last Name',
                    'key' => 'recipient_last_name'
                ],
                [
                    'label' => 'Email',
                    'key' => 'recipient_email'
                ],
                [
                    'label' => 'From',
                    'key' => 'awarder_full'
                ],
                [
                    'label' => 'Referrer',
                    'key' => 'referrer'
                ],
                [
                    'label' => 'Notes',
                    'key' => 'notes'
                ],
                [
                    'label' => 'Dollar Value',
                    'key' => 'dollar_value'
                ],
            ];
        } else {
            return [
                [
                    'label' => 'Program Name',
                    'key' => 'program_name'
                ],
                [
                    'label' => 'Program Id',
                    'key' => 'program_id'
                ],
                [
                    'label' => 'External Id',
                    'key' => 'external_id'
                ],
                [
                    'label' => 'Event',
                    'key' => 'event_name'
                ],
                [
                    'label' => 'GL Code',
                    'key' => 'ledger_code'
                ],
                [
                    'label' => 'Award Level',
                    'key' => 'award_level_name'
                ],
                [
                    'label' => 'Date',
                    'key' => 'posting_timestamp'
                ],
                [
                    'label' => 'First Name',
                    'key' => 'recipient_first_name'
                ],
                [
                    'label' => 'Program Name',
                    'key' => 'program_name'
                ],
                [
                    'label' => 'Last Name',
                    'key' => 'recipient_last_name'
                ],
                [
                    'label' => 'Email',
                    'key' => 'recipient_email'
                ],
                [
                    'label' => 'From',
                    'key' => 'awarder_full'
                ],
                [
                    'label' => 'Referrer',
                    'key' => 'referrer'
                ],
                [
                    'label' => 'Notes',
                    'key' => 'notes'
                ],
                [
                    'label' => 'Value',
                    'key' => 'points'
                ],
                [
                    'label' => 'Dollar Value',
                    'key' => 'dollar_value'
                ],
            ];
        }

    }

}
