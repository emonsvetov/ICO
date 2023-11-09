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
        $program = Program::where('account_holder_id', $this->params[self::PROGRAM_ACCOUNT_HOLDER_ID])->first();
        if ($program->programIsInvoiceForAwards()) {
            $account_type = AccountType::ACCOUNT_TYPE_POINTS_AWARDED;
        } else {
            $account_type = AccountType::ACCOUNT_TYPE_MONIES_AWARDED;
        }

        $query = DB::table('accounts');
        $query->join('account_types', function ($join) use ($account_type) {
            $join->on('account_types.id', '=', 'accounts.account_type_id');
            $join->on('account_types.name', '=', DB::raw("'{$account_type}'"));
        });
        $query->join('postings', function ($join) use ($account_type) {
            $join->on('postings.account_id', '=', 'accounts.id');
//            $join->on('postings.is_credit', '=', DB::raw("'1'"));
        });
        $query->join('journal_events', 'journal_events.id', '=', 'postings.journal_event_id');
        $query->join('journal_event_types', 'journal_event_types.id', '=', 'journal_events.journal_event_type_id');
        $query->leftJoin('event_xml_data', 'event_xml_data.id', '=', 'journal_events.event_xml_data_id');

        $query->selectRaw("
            if(`event_xml_data`.`name` is null, `journal_event_types`.`type`, `event_xml_data`.`name`) AS event_name,
            if(`event_xml_data`.`notes` is null, `journal_events`.`notes`, `event_xml_data`.`notes`) AS event_notes,
            `journal_events`.`created_at` as event_date,
            if(`postings`.`is_credit` = 1, `postings`.`posting_amount`, -`postings`.`posting_amount`) AS amount,
            `postings`.`is_credit`,
            `postings`.`is_credit` as is_credit2,
            '{$program->name}' as program
        ");

        return $query;
    }

    /**
     * @inheritDoc
     */
    protected function setWhereFilters(Builder $query): Builder
    {
        $query->where('accounts.account_holder_id', '=', $this->params[self::USER_ACCOUNT_HOLDER_ID]);
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
