<?php

namespace App\Services\reports;

use App\Models\AccountType;
use App\Models\EventType;
use App\Models\JournalEventType;
use App\Models\Program;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ReportDepositBalanceService extends ReportServiceAbstract
{

    /**
     * @inheritDoc
     */
    protected function calc(): array
    {
        try {
            $extras = [
                'extra' => $this->getSqlExtra(),
                'balance' => $this->getSqlBalance(),
                'startBalance' => $this->getSqlStartBalance(),
            ];

            $table = $this->calcReportsExtra($extras);
        } catch (\Exception $exception) {
            $error = $exception->getMessage();
            die;
        }

        $this->table['data'] = $table;
        $this->table['total'] = count($table);

        return $this->table;
    }

    /**
     * @inheritDoc
     */
    protected function calcReportsExtra($extras)
    {
        $programsArray = [];
        $programIDs = $this->params[self::PROGRAMS];
        $programs = Program::whereIn('account_holder_id', $programIDs)->get()->keyBy('account_holder_id')->toArray();
        foreach ($programIDs as $programID) {
            $reversalTotal = $depositTotal = $transferTotal = $awardTotal = $reclaimTotal = $endBalanceTotalCredit = $endBalanceTotalDebit = $startBalanceTotalCredit = $startBalanceTotalDebit = 0;
            $programsArray[$programID]['programName'] = $programs[$programID]['name'] ?? '';
            $programsArray[$programID]['programID'] = $programID;
            foreach ($extras['startBalance'] as $extraStartBalance) {
                if ($extraStartBalance->account_holder_id == $programID) {
                    $programsArray[$programID]['name'] = $extraStartBalance->name;
                    if ($extraStartBalance->is_credit) {
                        $startBalanceTotalCredit = $extraStartBalance->total;
                    }

                    if (!$extraStartBalance->is_credit) {
                        $startBalanceTotalDebit = $extraStartBalance->total;
                    }
                }

            }
            $programsArray[$programID]['startBalanceTotal'] = $startBalanceTotalCredit - $startBalanceTotalDebit;

            foreach ($extras['extra'] as $extra) {
                if ($extra->program_id == $programID) {
                    $programsArray[$programID]['programName'] = $programs[$programID]['name'] ?? '';
                    $programsArray[$programID]['programID'] = $programID;
                    if (($extra->is_credit && $extra->event_type == EventType::EVENT_TYPE_PROGRAM_PAYS_FOR_MONIES_PENDING && $extra->account_type == AccountType::ACCOUNT_TYPE_MONIES_AVAILABLE)
                        or ($extra->is_credit && $extra->event_type == EventType::EVENT_TYPE_PROGRAM_TRANSFERS_MONIES_AVAILABLE && $extra->account_type == AccountType::ACCOUNT_TYPE_MONIES_AVAILABLE)) {
                        $depositTotal += $extra->posting_amount;
                        $programsArray[$programID]['deposit'] = $depositTotal;
                    }
                    $reversal_types = array(
                        EventType::EVENT_TYPE_REVERSAL_PROGRAM_PAYS_FOR_MONIES_PENDING,
                        EventType::EVENT_TYPE_REVERSAL_PROGRAM_PAYS_FOR_DEPOSIT_FEE,
                    );
                    if ((!$extra->is_credit && in_array($extra->event_type,
                            $reversal_types) && $extra->account_type == AccountType::ACCOUNT_TYPE_MONIES_DUE_TO_OWNER)) {
                        $reversalTotal += $extra->posting_amount;
                        $programsArray[$programID]['reversal'] = $reversalTotal;
                    }

                    if (!$extra->is_credit && $extra->event_type == EventType::EVENT_TYPE_PROGRAM_TRANSFERS_MONIES_AVAILABLE && $extra->account_type == AccountType::ACCOUNT_TYPE_MONIES_AVAILABLE) {
                        $transferTotal += $extra->posting_amount;
                        $programsArray[$programID]['transfer'] = $transferTotal;
                    }

                    if (!$extra->is_credit && $extra->event_type == EventType::EVENT_TYPE_AWARD_MONIES_TO_RECIPIENT && $extra->account_type == AccountType::ACCOUNT_TYPE_MONIES_AVAILABLE) {
                        $awardTotal += $extra->posting_amount;
                        $programsArray[$programID]['award'] = $awardTotal;
                    }

                    if ($extra->is_credit && $extra->event_type == EventType::EVENT_TYPE_RECLAIM_MONIES && $extra->account_type == AccountType::ACCOUNT_TYPE_MONIES_AVAILABLE) {
                        $reclaimTotal += $extra->posting_amount;
                        $programsArray[$programID]['reclaim'] = $reclaimTotal;
                    }
                }
            }

            foreach ($extras['balance'] as $extraBalance) {
                if ($extraBalance->account_holder_id == $programID) {
                    if ($extraBalance->is_credit) {
                        $endBalanceTotalCredit = $extraBalance->total;
                    }

                    if (!$extraBalance->is_credit) {
                        $endBalanceTotalDebit = $extraBalance->total;
                    }
                }

            }
            $programsArray[$programID]['endBalanceTotal'] = $programsArray[$programID]['startBalanceTotal'] + $depositTotal - $awardTotal - $transferTotal + $reclaimTotal - $reversalTotal;

        }

        $arr = [];
        foreach ($programsArray as $programItem) {
            $arr[] = (object) $programItem;
        }

        return $arr;
    }

    /**
     * @inheritDoc
     */
    protected function getSqlExtra()
    {
        $query = DB::table('accounts as a');
        $query->join('programs as p', 'p.account_holder_id', '=', 'a.account_holder_id');
        $query->join('account_types as atypes', 'atypes.id', '=', 'a.account_type_id');
        $query->join('postings as posts', 'posts.account_id', '=', 'a.id');
        $query->join('journal_events as je', 'je.id', '=', 'posts.journal_event_id');
        $query->join('journal_event_types as jet', 'jet.id', '=', 'je.journal_event_type_id');

        $query->selectRaw("
            p.name,
            p.id,
            posts.posting_amount,
            posts.is_credit,
            jet.type as event_type,
            a.account_holder_id as program_id,
            atypes.name as account_type
        "
        );

        $query->whereBetween('posts.created_at', [$this->params[self::DATE_BEGIN], $this->params[self::DATE_END]]);
        $query->whereIn('a.account_holder_id', $this->params[self::PROGRAMS]);

        return $query->get();
    }

    /**
     * @inheritDoc
     */
    protected function getSqlBalance()
    {
        $query = DB::table('accounts as a');
        $query->join('account_types as at', 'at.id', '=', 'a.account_type_id');
        $query->join('postings as p', 'p.account_id', '=', 'a.id');
        $query->join('journal_events as je', 'je.id', '=', 'p.journal_event_id');
        $query->join('journal_event_types as jet', 'jet.id', '=', 'je.journal_event_type_id');

        $query->selectRaw("
            a.account_holder_id,
            COUNT(0) AS COUNT,
            at.name,
            ROUND(SUM(p.posting_amount * p.qty),2) AS total,
            p.is_credit
        "
        );

        $query->whereIn('a.account_holder_id', $this->params[self::PROGRAMS]);
        $query->where('at.name', AccountType::ACCOUNT_TYPE_MONIES_AVAILABLE);

        $query->groupBy(['a.account_holder_id', 'p.is_credit']);

        return $query->get();
    }

    /**
     * @inheritDoc
     */
    protected function getSqlStartBalance()
    {
        $query = DB::table('accounts as a');
        $query->join('programs as prog', 'prog.account_holder_id', '=', 'a.account_holder_id');
        $query->join('account_types as at', 'at.id', '=', 'a.account_type_id');
        $query->join('postings as p', 'p.account_id', '=', 'a.id');
        $query->join('journal_events as je', 'je.id', '=', 'p.journal_event_id');
        $query->join('journal_event_types as jet', 'jet.id', '=', 'je.journal_event_type_id');

        $query->selectRaw("
            prog.name,
            a.account_holder_id,
            COUNT(0) AS COUNT,
            at.name,
            ROUND(SUM(p.posting_amount * p.qty),2) AS total,
            p.is_credit
        "
        );

        $query->whereIn('a.account_holder_id', $this->params[self::PROGRAMS]);
        $query->where('at.name', AccountType::ACCOUNT_TYPE_MONIES_AVAILABLE);
        $query->where('p.created_at', '<=', $this->params[self::DATE_BEGIN]);

        $query->groupBy(['a.account_holder_id', 'p.is_credit']);

        return $query->get();
    }

    /**
     * @inheritDoc
     */
    protected function getReportForCSV(): array
    {
        $this->isExport = true;
        $this->params[self::SQL_LIMIT] = null;
        $this->params[self::SQL_OFFSET] = null;
        $data = $this->getTable();

        $data['headers'] = $this->getCsvHeaders();
        return $data;
    }

    /**
     * @inheritDoc
     */
    public function getCsvHeaders(): array
    {
        return [
            [
                'label' => 'Program Name',
                'key' => 'name',
            ],
            [
                'label' => 'Beginning Balance',
                'key' => 'startBalanceTotal',
            ],
            [
                'label' => 'Total Deposits',
                'key' => 'deposit',
            ],
            [
                'label' => 'Total Reversal',
                'key' => 'reversal',
            ],
            [
                'label' => 'Transfer',
                'key' => 'transfer',
            ],
            [
                'label' => 'Total Awarded',
                'key' => 'award',
            ],
            [
                'label' => 'Total Reclaims',
                'key' => 'reclaim',
            ],
            [
                'label' => 'Ending Balance',
                'key' => 'endBalanceTotal',
            ],
        ];
    }

}
