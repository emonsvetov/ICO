<?php

namespace App\Services\reports;

use App\Models\AccountType;
use App\Models\JournalEventType;
use App\Models\MediumInfo;
use App\Models\Merchant;
use App\Models\OptimalValue;
use App\Models\Program;
use App\Models\User;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Expr\Cast\Object_;
use stdClass;

class ReportPointsPurchaseSummaryService extends ReportServiceAbstract
{
    public $totals = [];
    public $defaultValues = [];

    /**
     * @inheritDoc
     */
    protected function getReportForCSV(): array
    {
        $this->isExport = true;
        $this->params[self::SQL_LIMIT] = null;
        $this->params[self::SQL_OFFSET] = null;
        $data = $this->getTable();

        $total = [
            'participants_count' => 0,
            'month_1' => 0,
            'month_2' => 0,
            'month_3' => 0,
            'month_4' => 0,
            'month_5' => 0,
            'month_6' => 0,
            'month_7' => 0,
            'month_8' => 0,
            'month_9' => 0,
            'month_10' => 0,
            'month_11' => 0,
            'month_12' => 0,
            'YTD' => 0,
            'per_participant' => 0,
            'avg_per_month' => 0,
            'monthly_target' => 0,
            'annual_target' => 0,
        ];
        $empty = [
            'name' => '',
            'participants_count' => '',
            'month_1' => '',
            'month_2' => '',
            'month_3' => '',
            'month_4' => '',
            'month_5' => '',
            'month_6' => '',
            'month_7' => '',
            'month_8' => '',
            'month_9' => '',
            'month_10' => '',
            'month_11' => '',
            'month_12' => '',
            'YTD' => '',
            'per_participant' => '',
            'avg_per_month' => '',
            'monthly_target' => '',
            'annual_target' => '',
        ];
        foreach ($data['data'] as $key => $item) {
            if ($item->dinamicDepth !== 1) {
                unset($data['data'][$key]);
            }

            foreach ($total as $subKey => $subItem) {
                $total[$subKey] += $item->{$subKey};
            }
        }

        $total['name'] = 'Total';
        $data['data'][] = $empty;
        $data['data'][] = $total;

        $data['data'] = $data['data'];
        $data['total'] = count($data['data']);
        $data['headers'] = $this->getCsvHeaders();
        return $data;
    }

    protected function calc(): array
    {
        $table = [];
        $this->table = [];

        $this->params[self::DATE_FROM] = $this->params[self::YEAR] . '-01-01 00:00:00';
        $this->params[self::DATE_TO] = $this->params[self::YEAR] . '-12-31 23:59:59';
        $dateBegin = date('Y-m-d 00:00:00', strtotime($this->params[self::DATE_FROM]));
        $dateEnd = date('Y-m-d 23:59:59', strtotime($this->params[self::DATE_TO]));
        $programAccountHolderIds = $this->params[self::PROGRAMS];
        $programs = Program::whereIn('account_holder_id', $programAccountHolderIds)->get();

        if ($programs) {
            $programIds = $programs->pluck('id')->toArray();
            $topLevelProgramData = $programs[0]->getRoot(['id', 'name']);
            $topLevelProgram = Program::find($topLevelProgramData->id);

            $programs = (new Program)->whereIn('account_holder_id', $programAccountHolderIds)->get()->toTree();
            $programs = _tree_flatten($programs);

            foreach ($programs as $program) {
                $program = (object)$program->toArray();

                $table[$program->account_holder_id] = $program;
                $table[$program->account_holder_id]->program_name = $program->name;

                $table[$program->account_holder_id]->participants_count = 0;
                $table[$program->account_holder_id]->per_participant = 0;
                $table[$program->account_holder_id]->avg_per_month = 0;
                $table[$program->account_holder_id]->avg_per_quarter = 0;
                $table[$program->account_holder_id]->monthly_target = 0;
                $table[$program->account_holder_id]->quarterly_target = 0;
                $table[$program->account_holder_id]->annual_target = 0;
                $table[$program->account_holder_id]->month_1 = 0;
                $table[$program->account_holder_id]->month_2 = 0;
                $table[$program->account_holder_id]->month_3 = 0;
                $table[$program->account_holder_id]->month_4 = 0;
                $table[$program->account_holder_id]->month_5 = 0;
                $table[$program->account_holder_id]->month_6 = 0;
                $table[$program->account_holder_id]->month_7 = 0;
                $table[$program->account_holder_id]->month_8 = 0;
                $table[$program->account_holder_id]->month_9 = 0;
                $table[$program->account_holder_id]->month_10 = 0;
                $table[$program->account_holder_id]->month_11 = 0;
                $table[$program->account_holder_id]->month_12 = 0;
                $table[$program->account_holder_id]->Q1 = 0;
                $table[$program->account_holder_id]->Q2 = 0;
                $table[$program->account_holder_id]->Q3 = 0;
                $table[$program->account_holder_id]->Q4 = 0;
                $table[$program->account_holder_id]->YTD = 0;
            }

            // Get the Eligible Participants for each program
            $userStatuses = [
                config('global.user_status_pending_activation'),
                config('global.user_status_locked'),
                config('global.user_status_pending_deactivation'),
                config('global.user_status_active'),
            ];
            $countParticipants = $this->reportHelper->countParticipantsByUserStatuses($userStatuses, $dateBegin, $dateEnd, $programIds);
            foreach ($countParticipants as $program_id => $participant_count) {
                if (isset($table[$program_id])) {
                    $table[$program_id]->participants_count = $participant_count;
                    $table[$program_id]->annual_target = $this->params [self::TARGET_PARTICIPANT] * ( int )$participant_count;
                    $table[$program_id]->monthly_target = $table[$program_id]->annual_target / 12;
                    $table[$program_id]->quarterly_target = $table[$program_id]->annual_target / 4;
                }
            }

            // Get Awards
            $args = [];
            $args['accountTypes'] = [
                AccountType::ACCOUNT_TYPE_MONIES_AWARDED,
            ];
            $args['journalEventTypes'] = [
                JournalEventType::JOURNAL_EVENT_TYPES_AWARD_MONIES_TO_RECIPIENT,
            ];
            $args['months'] = true;
            $args['isCredit'] = true;
            $args['programAccountHolderIds'] = $programAccountHolderIds;
            $credits_report = $this->reportHelper->sumProgramAwardPoints($dateBegin, $dateEnd, $args);

            foreach ($credits_report as $program_account_holder_id => $programs_credits_report_table) {
                $program = $table[$program_account_holder_id];
                foreach ($programs_credits_report_table as $account_type_name => $account) {
                    if (!$program->invoice_for_awards) {
                        foreach ($account as $months) {
                            foreach ($months as $month => $amount) {
                                $table[$program->account_holder_id]->{'month_' . $month} += $this->amountFormat($amount);
                                $quarter = ceil($month / 3);
                                $table[$program->account_holder_id]->{'Q' . $quarter} += $this->amountFormat($amount);
                                $table[$program->account_holder_id]->YTD += $this->amountFormat($amount);
                            }
                        }
                    }
                }
            }

            // Get Awards
            $args = [];
            $args['accountTypes'] = [
                AccountType::ACCOUNT_TYPE_POINTS_AWARDED,
            ];
            $args['journalEventTypes'] = [
                JournalEventType::JOURNAL_EVENT_TYPES_AWARD_POINTS_TO_RECIPIENT
            ];
            $args['months'] = true;
            $args['isCredit'] = true;
            $args['programAccountHolderIds'] = $programAccountHolderIds;
            $credits_report = $this->reportHelper->sumProgramAwardPoints($dateBegin, $dateEnd, $args);

            foreach ($credits_report as $program_account_holder_id => $programs_credits_report_table) {
                $program = $table[$program_account_holder_id];
                foreach ($programs_credits_report_table as $account_type_name => $account) {
                    if ($program->invoice_for_awards) {
                        foreach ($account as $months) {
                            foreach ($months as $month => $amount) {
                                $table[$program->account_holder_id]->{'month_' . $month} += $this->amountFormat($amount);
                                $quarter = ceil($month / 3);
                                $table[$program->account_holder_id]->{'Q' . $quarter} += $this->amountFormat($amount);
                                $table[$program->account_holder_id]->YTD += $this->amountFormat($amount);
                            }
                        }
                    }
                }
            }

            // Get Reclaims
            $args = [];
            $args['accountTypes'] = [
                AccountType::ACCOUNT_TYPE_MONIES_DUE_TO_OWNER,
                AccountType::ACCOUNT_TYPE_MONIES_AVAILABLE
            ];
            $args['journalEventTypes'] = [
                JournalEventType::JOURNAL_EVENT_TYPES_RECLAIM_POINTS,
                JournalEventType::JOURNAL_EVENT_TYPES_RECLAIM_MONIES
            ];
            $args['months'] = true;
            $args['isCredit'] = true;
            $args['programAccountHolderIds'] = $programAccountHolderIds;
            $credits_report = $this->reportHelper->sumPostsByAccountAndJournalEventAndCredit($dateBegin, $dateEnd, $args);

            foreach ($credits_report as $program_account_holder_id => $programs_credits_report_table) {
                $program = $table[$program_account_holder_id];
                foreach ($programs_credits_report_table as $account_type_name => $account) {

                    foreach ($account as $journal_event_type => $months) {
                        switch ($account_type_name) {
                            case AccountType::ACCOUNT_TYPE_MONIES_DUE_TO_OWNER :
                                switch ($journal_event_type) {
                                    case JournalEventType::JOURNAL_EVENT_TYPES_RECLAIM_POINTS :
                                        foreach ($months as $month => $amount) {
                                            $table[$program->account_holder_id]->{'month_' . $month} -= $this->amountFormat($amount);
                                            $quarter = ceil($month / 3);
                                            $table[$program->account_holder_id]->{'Q' . $quarter} -= $this->amountFormat($amount);
                                            $table[$program->account_holder_id]->YTD -= $this->amountFormat($amount);
                                        }
                                        break;
                                }
                                break;
                            case AccountType::ACCOUNT_TYPE_MONIES_AVAILABLE :
                                switch ($journal_event_type) {
                                    case JournalEventType::JOURNAL_EVENT_TYPES_RECLAIM_MONIES :
                                        foreach ($months as $month => $amount) {
                                            $table[$program->account_holder_id]->{'month_' . $month} -= $this->amountFormat($amount);
                                            $quarter = ceil($month / 3);
                                            $table[$program->account_holder_id]->{'Q' . $quarter} -= $this->amountFormat($amount);
                                            $table[$program->account_holder_id]->YTD -= $this->amountFormat($amount);
                                        }
                                        break;
                                }
                        }
                    }
                }
            }

            // Calc Averages
            $month = 12;
            $quarter = 4;
            // If the report is being pulled for the current year, use the current month/quarter for the averages so they aren't skewed.
            if (date('Y') == $this->params[self::YEAR]) {
                $month = date('m');
                $quarter = ceil($month / 3);
            }
            foreach ($table as $program) {
                if ($program->participants_count > 0) {
                    $program->per_participant = $this->amountFormat($program->YTD / $program->participants_count);
                }
                $program->avg_per_month = $this->amountFormat($program->YTD / $month);
                $program->avg_per_quarter = $this->amountFormat($program->YTD / $quarter);
            }

            $newTable = [];
            $this->defaultValues = [
                'participants_count' => 0,
                'month_1' => 0,
                'month_2' => 0,
                'month_3' => 0,
                'month_4' => 0,
                'month_5' => 0,
                'month_6' => 0,
                'month_7' => 0,
                'month_8' => 0,
                'month_9' => 0,
                'month_10' => 0,
                'month_11' => 0,
                'month_12' => 0,
                'per_participant' => 0,
                'avg_per_month' => 0,
                'avg_per_quarter' => 0,
                'monthly_target' => 0,
                'quarterly_target' => 0,
                'annual_target' => 0,
                'Q1' => 0,
                'Q2' => 0,
                'Q3' => 0,
                'Q4' => 0,
                'YTD' => 0,
            ];
            foreach ($table as $key => $item) {
                if ($item->parent_id == $programs[0]->parent_id) {
                    $newTable[$item->id] = clone $item;
                } else {
                    $tmpPath = explode(',', $item->dinamicPath);
                    $tmpPath = array_diff($tmpPath, explode(',',$programs[0]->dinamicPath));
                    $first = reset($tmpPath);

                    if (isset($newTable[$first])) {
                        $newTable = $this->tableToTree($newTable, $item, $tmpPath, 0, $this->defaultValues);
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

            $this->table = array_values($newTable);
        }

        return [];
    }

    public function tableToTree($newTable, $item, $path, $level = 0, $defaultValues = [])
    {
        $first = array_shift($path);
        $tableKey = $level === 0 ? $first : array_search($first, array_column($newTable, 'id'));

        if (count($path) === 0) {
            foreach ($defaultValues as $keyValue => $value) {
                $item->$keyValue = $keyValue == 'participants_count' ? $item->$keyValue : $this->amountFormat($item->$keyValue);
            }
            $newTable[$tableKey]->subRows[] = $item;
        } else {
            $level++;

            $subRows = $this->tableToTree($newTable[$tableKey]->subRows, $item, $path, $level, $defaultValues);
            foreach ($subRows as $key => $subRow) {
                foreach ($defaultValues as $keyValue => $value) {
                    $subRows[$key]->$keyValue = $keyValue == 'participants_count' ? $subRows[$key]->$keyValue : $this->amountFormat($subRows[$key]->$keyValue);
                }
            }

            $newTable[$tableKey]->subRows = $subRows;
        }
        return $newTable;
    }

    public function tableToTreeSubTotals($table)
    {
        foreach ($this->defaultValues as $keyValue => $value) {
            $this->totals[$keyValue] += $table->$keyValue;
        }

        if (
            isset($table->subRows) &&
            count($table->subRows) > 0
        ) {
            foreach ($table->subRows as $subTable)
            $this->tableToTreeSubTotals($subTable);
        }
    }

    public function getCsvHeaders(): array
    {
        return [
            [
                'label' => 'Program',
                'key' => 'name'
            ],
            [
                'label' => 'Program Account Holder ID',
                'key' => 'account_holder_id'
            ],
            [
                'label' => 'Eligible Participant',
                'key' => 'participants_count'
            ],
            [
                'label' => 'Jan',
                'key' => 'month_1'
            ],
            [
                'label' => 'Feb',
                'key' => 'month_2'
            ],
            [
                'label' => 'Mar',
                'key' => 'month_3'
            ],
            [
                'label' => 'Apr',
                'key' => 'month_4'
            ],
            [
                'label' => 'May',
                'key' => 'month_5'
            ],
            [
                'label' => 'Jun',
                'key' => 'month_6'
            ],
            [
                'label' => 'Jul',
                'key' => 'month_7'
            ],
            [
                'label' => 'Aug',
                'key' => 'month_8'
            ],
            [
                'label' => 'Sep',
                'key' => 'month_9'
            ],
            [
                'label' => 'Oct',
                'key' => 'month_10'
            ],
            [
                'label' => 'Nov',
                'key' => 'month_11'
            ],
            [
                'label' => 'Dec',
                'key' => 'month_12'
            ],
            [
                'label' => 'YTD',
                'key' => 'YTD'
            ],
            [
                'label' => 'Q1',
                'key' => 'Q1'
            ],
            [
                'label' => 'Q2',
                'key' => 'Q2'
            ],
            [
                'label' => 'Q3',
                'key' => 'Q3'
            ],
            [
                'label' => 'Q3',
                'key' => 'Q3'
            ],
            [
                'label' => 'Per Participant',
                'key' => 'per_participant'
            ],
            [
                'label' => 'Avg Per Month',
                'key' => 'avg_per_month'
            ],
            [
                'label' => 'Monthly Target',
                'key' => 'monthly_target'
            ],
            [
                'label' => 'Annual Target',
                'key' => 'annual_target'
            ],
        ];
    }


}
