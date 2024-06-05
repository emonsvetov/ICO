<?php
namespace App\Services\reports\User;

use App\Models\AccountType;
use App\Models\JournalEventType;
use App\Models\Program;
use App\Services\reports\ReportServiceAbstract as ReportServiceAbstractBase;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ReportServiceUserHistory extends ReportServiceAbstractBase
{

    /**
     * @inheritDoc
     */
    protected function getBaseQuery(): Builder
    {

        $programs = blank($this->params[self::PROGRAMS]) ?
            [$this->params[self::PROGRAM_ACCOUNT_HOLDER_ID]] :
            explode(',', $this->params[self::PROGRAMS]);

        $query = false;
        foreach ($programs as $programID) {
            $subQuery = DB::table('accounts');

            $program = Program::where('account_holder_id', $programID)->first();
            if ($program->programIsInvoiceForAwards()) {
                $account_type = AccountType::ACCOUNT_TYPE_POINTS_AWARDED;
            } else {
                $account_type = AccountType::ACCOUNT_TYPE_MONIES_AWARDED;
            }

            $subQuery->join('account_types', function ($join) use ($account_type) {
                $join->on('account_types.id', '=', 'accounts.account_type_id');
            });
            $subQuery->join('postings', function ($join) use ($account_type) {
                $join->on('postings.account_id', '=', 'accounts.id');
            });
            $subQuery->join('journal_events', 'journal_events.id', '=', 'postings.journal_event_id');
            $subQuery->join('journal_event_types', 'journal_event_types.id', '=', 'journal_events.journal_event_type_id');
            $subQuery->leftJoin('event_xml_data', 'event_xml_data.id', '=', 'journal_events.event_xml_data_id');

            $subQuery->selectRaw("
            if(`event_xml_data`.`name` is null, `journal_event_types`.`type`, `event_xml_data`.`name`) AS event_name,
            if(`event_xml_data`.`notes` is null, `journal_events`.`notes`, `event_xml_data`.`notes`) AS event_notes,
            `journal_events`.`created_at` as event_date,
            if(`postings`.`is_credit` = 1, `postings`.`posting_amount`, -`postings`.`posting_amount`) AS amount,
            `postings`.`is_credit`,
            `postings`.`is_credit` as is_credit2,
            (
                SELECT
                    SUM(if(p.is_credit = 1, p.posting_amount, -p.posting_amount)) as total
                FROM
                    `postings` p
                    INNER JOIN `journal_events` je ON je.id = p.journal_event_id
                    INNER JOIN `journal_event_types` jet ON jet.id = je.journal_event_type_id
                WHERE
                    p.account_id = `accounts`.id
                    AND jet.id = `journal_event_types`.id
            ) as event_total
            ");
            $subQuery->where(function ($query) {
            $query->where(DB::raw('(SELECT SUM(IF(p.is_credit = 1, p.posting_amount, -p.posting_amount)) AS total
                FROM postings p
                INNER JOIN journal_events je ON je.id = p.journal_event_id
                INNER JOIN journal_event_types jet ON jet.id = je.journal_event_type_id
                WHERE p.account_id = accounts.id
                AND jet.id = journal_event_types.id)'), '<>', 0);
            });

            $subQuery->where('accounts.account_holder_id', '=', $this->params[self::USER_ACCOUNT_HOLDER_ID]);
            $subQuery->whereIn('account_types.name', ['Points Awarded', 'Monies Awarded']);

            if (!$query) {
                $query = $subQuery;
            }
            else {
                $query->union($subQuery);
            }
        }



        return $query;
    }

    protected function setDefaultParams() {
        parent::setDefaultParams ();
        $this->params[self::PROGRAMS] = request()->get('programs');
    }

    /**
     * @inheritDoc
     */
    protected function setWhereFilters(Builder $query): Builder
    {
//        $query->where('accounts.account_holder_id', '=', $this->params[self::USER_ACCOUNT_HOLDER_ID]);
        return $query;
    }

    /**
     * @inheritDoc
     */
    protected function setGroupBy(Builder $query): Builder
    {
//        $query->groupBy(['event_xml_data.name']);
        return $query;
    }
}
