<?php

namespace App\Services\reports;

use App\Models\AccountType;
use App\Models\EventType;
use App\Models\JournalEventType;
use App\Models\Program;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ReportDepositBalanceService extends ReportServiceAbstract
{

    public $totals = [];
    public $defaultValues = [];
    public $tranfers = [];

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
        $programAccountHolderIds = $this->params[self::PROGRAMS];
        $programs = Program::whereIn('account_holder_id', $programAccountHolderIds)->get()->toTree();
        $programs = _tree_flatten($programs);

        foreach ($programs as $program) {

            $programID = $program->account_holder_id;

            $transferSubTotal = $reversalTotal = $depositTotal = $transferTotal = $awardTotal = $reclaimTotal = $endBalanceTotalCredit = $endBalanceTotalDebit = $startBalanceTotalCredit = $startBalanceTotalDebit = 0;
            $programsArray[$programID]['name'] = (isset($this->programs[$programID]) && isset($this->programs[$programID]->name))
                ? $this->programs[$programID]->name : '';
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
                    $programsArray[$programID]['name'] = $extra->name;
                    $programsArray[$programID]['programID'] = $extra->id;
                    if (($extra->is_credit
                            && $extra->event_type == EventType::EVENT_TYPE_PROGRAM_PAYS_FOR_MONIES_PENDING
                            && $extra->account_type == AccountType::ACCOUNT_TYPE_MONIES_AVAILABLE
                        )
                        or ($extra->is_credit
                            && $extra->event_type == EventType::EVENT_TYPE_PROGRAM_TRANSFERS_MONIES_AVAILABLE
                            && $extra->account_type == AccountType::ACCOUNT_TYPE_MONIES_AVAILABLE
                        )) {
                        $depositTotal += $extra->posting_amount;

                        if (
                            isset($this->tranfers[$extra->journal_event_id])
                            && $extra->posting_amount == $this->tranfers[$extra->journal_event_id]
                        ) {
                            $programsArray[$programID]['transfer'] = $extra->posting_amount;
                            $transferSubTotal += $extra->posting_amount;
                        }

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

                    if (
                        !$extra->is_credit
                        && $extra->event_type == EventType::EVENT_TYPE_PROGRAM_TRANSFERS_MONIES_AVAILABLE
                        && $extra->account_type == AccountType::ACCOUNT_TYPE_MONIES_AVAILABLE
                    ) {
                        $this->tranfers[$extra->journal_event_id] = $extra->posting_amount;
                        $transferTotal += $extra->posting_amount;
                        $programsArray[$programID]['transfer'] = $transferTotal * (-1);
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
            $programsArray[$programID]['deposit'] = $depositTotal - $transferTotal - $transferSubTotal;

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
            $programsArray[$programID]['account_holder_id'] = $program->account_holder_id;
            $programsArray[$programID]['v2_account_holder_id'] = $program->v2_account_holder_id;
            $programsArray[$programID]['parent_id'] = $program->parent_id;
            $programsArray[$programID]['dinamicPath'] = $program->dinamicPath;
            $programsArray[$programID]['dinamicDepth'] = $program->dinamicDepth;
            $programsArray[$programID]['id'] = $program->id;
            $programsArray[$programID]['name'] = $program->name;

        }

        $table = [];
        $this->defaultValues = [
            'startBalanceTotal' => 0,
            'deposit' => 0,
            'reversal' => 0,
            'transfer' => 0,
            'award' => 0,
            'reclaim' => 0,
            'refunds' => 0,
            'endBalanceTotal' => 0,
        ];

        foreach ($programsArray as $programItem) {
            $table[] = (object) $programItem;
        }

        foreach ($table as $key => $item) {
            if ($item->parent_id == $programs[0]->parent_id) {
                $newTable[$item->id] = clone $item;
            } else {
                $tmpPath = explode(',', $item->dinamicPath);
                $tmpPath = array_diff($tmpPath, explode(',',$programs[0]->dinamicPath));
                $first = reset($tmpPath);

                if (isset($newTable[$first])) {
                    $newTable = $this->tableToTree($newTable, $item, $tmpPath, 0, []);
                }
            }
        }

        foreach ($newTable as $key => $item) {
            $table = $item;
            if (
                isset($table->subRows) &&
                count($table->subRows) > 0
            ) {

                $subTotal = clone $item;
                $rootProgram = clone $item;
                $rootProgram->subRows = [];
                $subTotal->subRows = [];
                $subTotal->name = 'Total ' . $subTotal->name;

                $this->totals = $this->defaultValues;
                $this->tableToTreeSubTotals($item);

                foreach ($this->defaultValues as $valueKey => $value) {
                    $subTotal->$valueKey = $this->totals[$valueKey];
                    $newTable[$key]->$valueKey = $this->totals[$valueKey];

                }
                $rootProgram->disableTotalCalculation = TRUE;
                $subTotal->disableTotalCalculation = TRUE;

                $newTable[$key]->subRows[] = $subTotal;
                array_unshift($newTable[$key]->subRows, $rootProgram);
            }
        }



        return array_values($newTable);
    }

    public function tableToTreeSubTotals($table)
    {
        foreach ($this->defaultValues as $keyValue => $value) {
            $this->totals[$keyValue] += $table->$keyValue ?? 0;
        }

        if (
            isset($table->subRows) &&
            count($table->subRows) > 0
        ) {
            foreach ($table->subRows as $subTable)
                $this->tableToTreeSubTotals($subTable);
        }
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
            posts.journal_event_id,
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
