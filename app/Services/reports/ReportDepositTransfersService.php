<?php

namespace App\Services\reports;

use App\Models\AccountType;
use App\Models\JournalEventType;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ReportDepositTransfersService extends ReportServiceAbstract
{

    /**
     * @inheritDoc
     */
    protected function getBaseQuery(): Builder
    {
        $query = DB::table('postings');
        $query->join('journal_events', 'journal_events.id', '=', 'postings.journal_event_id');
        $query->join('journal_event_types', 'journal_event_types.id', '=', 'journal_events.journal_event_type_id');
        $query->join('accounts', 'accounts.id', '=', 'postings.account_id');
        $query->join('account_types', 'account_types.id', '=', 'accounts.account_type_id');
        $query->join('programs as from_program', 'from_program.account_holder_id', '=', 'accounts.account_holder_id');
        $query->leftJoin('users', 'users.account_holder_id', '=', 'journal_events.prime_account_holder_id');
        $query->join('postings as to_postings', function($join) {
            $join->on('to_postings.journal_event_id', '=', 'postings.journal_event_id');
            $join->on('to_postings.is_credit', '=', DB::raw("'1'"));
        });
        $query->join('accounts as to_account', 'to_account.id', '=', 'to_postings.account_id');
        $query->join('account_types as to_account_types', 'to_account_types.id', '=', 'to_account.account_type_id');
        $query->join('programs as to_program', 'to_program.account_holder_id', '=', 'to_account.account_holder_id');

        $query->selectRaw("
            from_program.name as from_program_name,
            from_program.account_holder_id as from_program_account_holder_id,
            to_program.name as to_program_name,
            to_program.account_holder_id as to_program_account_holder_id,
            cast(postings.posting_amount as float) as posting_amount,
            users.account_holder_id as user_id,
            users.first_name,
            users.last_name,
            CONCAT(`users`.first_name, ' ', `users`.last_name) as name,
            users.email,
            " . DB::raw("DATE_FORMAT(postings.created_at, '%d/%m/%Y') AS posting_timestamp") . "
        "
        );

        return $query;
    }

    /**
     * @inheritDoc
     */
    protected function setWhereFilters(Builder $query): Builder
    {
        $query->where('journal_event_types.type', JournalEventType::JOURNAL_EVENT_TYPES_PROGRAM_TRANSFERS_MONIES_AVAILABLE);
        $query->where('account_types.name', AccountType::ACCOUNT_TYPE_MONIES_AVAILABLE);
        $query->where('to_account_types.name', AccountType::ACCOUNT_TYPE_MONIES_AVAILABLE);
        $query->where('postings.is_credit', 0);
        $query->whereBetween('postings.created_at', [$this->params[self::DATE_FROM], $this->params[self::DATE_TO]]);
        $query->where(function ($q) {
            $q->whereIn('from_program.account_holder_id', $this->params[self::PROGRAMS]);
            $q->orWhereIn('to_program.account_holder_id', $this->params[self::PROGRAMS]);
        });

        return $query;
    }

    /**
     * @inheritDoc
     */
    public function getCsvHeaders(): array
    {
        return [
            [
                'label' => 'Date',
                'key' => 'posting_timestamp',
            ],
            [
                'label' => 'From Program',
                'key' => 'from_program_name',
            ],
            [
                'label' => 'To Program',
                'key' => 'to_program_name',
            ],
            [
                'label' => 'Transferred By',
                'key' => 'name',
            ],
            [
                'label' => 'Amount',
                'key' => 'posting_amount',
            ],
        ];
    }

}
