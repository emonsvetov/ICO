<?php

namespace App\Services\reports;

use App\Models\JournalEventType;
use Exception;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\QueryException;

class ReportTrialBalanceService extends ReportServiceAbstract
{

    /**
     * @inheritDoc
     */
    protected function getBaseQuery(): Builder
    {
        return DB::table(function ($subQuery) {
            $subQuery->from('postings');
            $subQuery->join('accounts', 'accounts.id', '=', 'postings.account_id');
            $subQuery->join('finance_types', 'finance_types.id', '=', 'accounts.finance_type_id');
            $subQuery->join('account_types', 'account_types.id', '=', 'accounts.account_type_id');

            $subQuery->selectRaw("
                postings.id,
                postings.account_id,
                postings.posting_amount,
                postings.qty,
                postings.is_credit,
                postings.created_at,
                account_types.name as account_type_name,
                finance_types.name as finance_type_name,
                CASE
                    WHEN EXISTS(SELECT `merchants`.account_holder_id FROM merchants WHERE merchants.account_holder_id = accounts.account_holder_id)
                    THEN 'Merchants'
                    WHEN EXISTS(SELECT `programs`.account_holder_id FROM programs WHERE programs.account_holder_id = accounts.account_holder_id)
                    THEN 'Programs'
                    WHEN EXISTS(SELECT `owners`.account_holder_id FROM owners WHERE owners.account_holder_id = accounts.account_holder_id)
                    THEN 'Owners'
                    ELSE 'Recipients'
                END AS account_holder
            ");
        }, 'subQuery')
            ->selectRaw("
				account_type_name,
				finance_type_name,
				SUM(posting_amount * qty) AS amount,
				is_credit,
				account_holder
            ")
            ->whereBetween('created_at', [$this->params[self::DATE_FROM], $this->params[self::DATE_TO]])
            ->groupBy(['account_type_name', 'finance_type_name', 'is_credit', 'account_holder']);
        return $query;
    }

    /**
     * @inheritDoc
     */
    protected function calc()
    {
        $this->table = [];
        if (isset($this->params[self::SQL_LIMIT])){
            $this->params[self::SQL_LIMIT] = null;
        }
        $this->getDataDateRange();

        $final_data = [];
        $data = [
            'merchants' => [],
            'recipients' => [],
            'programs' => [],
            'owners' => []
        ];

        if (count($this->table) > 0) {
            foreach ($this->table as $key => $value) {
                $data[strtolower($value->account_holder)] [] = $value;
            }
            foreach ($data as $types => $information) {
                foreach ($data[$types] as $key => $value) {
                    $final_data[$types][$value->account_type_name][$value->is_credit] = [
                        $value->finance_type_name,
                        $value->amount,
                        $value->is_credit,
                        $value->account_holder
                    ];
                }
            }
        }

        $result = [];
        $total = [
            'asset1' => 0,
            'liability1' => 0,
            'revenue1' => 0,
            'asset2' => 0,
            'liability2' => 0,
            'revenue2' => 0,
        ];;
        foreach ($final_data as $key => $item) {
            foreach ($item as $subKey => $subItem){
                $result[$key][$subKey] = [
                    'asset1' => 0,
                    'liability1' => 0,
                    'revenue1' => 0,
                    'asset2' => 0,
                    'liability2' => 0,
                    'revenue2' => 0,
                ];
                foreach ($subItem as $subKey2 => $subItem2) {
                    $index = $subItem2[2] === 0 ? 1 : 2;
                    $index = strtolower($subItem2[0]) . $index;
                    $value = number_format(round($subItem2[1], 2, PHP_ROUND_HALF_DOWN), 2);
                    $result[$key][$subKey][$index] = $value;
                    $total[$index] = $total[$index] + $subItem2[1];
                }
            }
        }
        foreach ($total as $key => $item){
            $total[$key] = number_format(round($item, 2, PHP_ROUND_HALF_DOWN), 2);
        }


        $this->table = [
            'data1' => $result,
            'total1' => $total,
        ];
    }

    protected function getReportForCSV(): array
    {
        $this->isExport = true;
        $this->params[self::SQL_LIMIT] = null;
        $this->params[self::SQL_OFFSET] = null;
        $tmpData = $this->getTable();

        $newData = [];
        foreach ($tmpData['data1'] as $key => $item){
            $i = 0;
            foreach ($item as $subKey => $subItem){
                $arr = [];
                if ($i === 0){
                    $arr['account_holder'] = $key;
                } else {
                    $arr['account_holder'] = '';
                }
                $arr['account'] = $subKey;
                foreach ($subItem as $subKey2 => $subItem2) {
                    $arr[$subKey2] = $subItem2;
                }
                $i++;
                $newData[] = $arr;
            }

        }
        $arr = [];
        $arr['account_holder'] = 'Total';
        $arr['account'] = '';
        foreach ($tmpData['total1'] as $key => $item){
            $arr[$key] = $item;
        }
        $newData[] = $arr;

        $data = [];
        $data['data'] = $newData;
        $data['total'] = count($newData);
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
                'label' => 'Account Holder',
                'key' => 'account_holder',
            ],
            [
                'label' => 'Account',
                'key' => 'account',
            ],
            [
                'label' => 'Asset',
                'key' => 'asset1',
            ],
            [
                'label' => 'Liability',
                'key' => 'liability1',
            ],
            [
                'label' => 'Revenue',
                'key' => 'revenue1',
            ],
            [
                'label' => 'Asset',
                'key' => 'asset2',
            ],
            [
                'label' => 'Liability',
                'key' => 'liability2',
            ],
            [
                'label' => 'Revenue',
                'key' => 'revenue2',
            ],
        ];
    }

}
