<?php

namespace App\Services\reports;

use App\Models\MediumInfo;
use App\Models\Merchant;
use App\Models\OptimalValue;
use App\Models\ProgramMerchant;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Expr\Cast\Object_;
use stdClass;

class ReportSupplierRedemptionService extends ReportServiceAbstract
{
    const FIELD_REDEMPTION_VALUE = 'redemption_value';

    private $total = [];
    public $cardSum = [];

    protected function calc()
    {
        $this->table = [];
        $query = $this->getBaseQuery();
        $query = $this->setWhereFilters($query);
        $this->table['data'] = $query->get()->toArray();        
    }

    protected function getBaseQuery(): Builder
    {
        $query = DB::table('merchants');
        $query->join('medium_info', 'medium_info.merchant_id', '=', 'merchants.id');
        $query->addSelect(
            DB::raw("
                merchants.name,
                cost_basis,
                sku_value,
                redemption_value,
                merchant_id as id"),
        );
        return $query;
    }

    /**
     * @inheritDoc
     */
    protected function setWhereFilters(Builder $query): Builder
    {
        $query->whereNull('merchants.deleted_at');
        $query->whereNotNull('medium_info.redemption_date');
        if ($this->params['programId']){
            $merchantIds = ProgramMerchant::where('program_id', $this->params['programId'])->pluck('merchant_id')->toArray();
            $query->whereIn('merchants.id', $merchantIds);
        }


        if (!empty($this->params['merchants'])) {
            $query->whereIn('merchants.id', $this->params['merchants']);
        }

        if (!empty($this->params['active'])) {
            $query->where('merchants.status', $this->params['active']);
        }

        if (!empty($this->params['codes'])) {
            $query->where('medium_info.virtual_inventory', $this->params['codes']);
        }

        if (!empty($this->params['from'])) {
            $query->where('medium_info.redemption_datetime', '>=', $this->params['from']);
        }

        if (!empty($this->params['to'])) {
            $toDateTime = \DateTime::createFromFormat('Y-m-d H:i:s', $this->params['to']);
            $toDateTime->setTime(23, 59, 59);
            $toDateTimeFormatted = $toDateTime->format('Y-m-d H:i:s');
            $query->where('medium_info.redemption_datetime', '<=', $toDateTimeFormatted);
        }

        return $query;
    }

    /**
     * @inheritDoc
     */
    protected function setGroupBy(Builder $query): Builder
    {
        $query->groupBy('sku_value', 'redemption_value', 'merchants.id');
        return $query;
    }

    public function getTable(): array
    {
        parent::getTable();

        $report_key = $this->params[self::FIELD_REPORT_KEY] ?? self::FIELD_REDEMPTION_VALUE;
        $data = $this->table['data'];
        $bodyReference = [];
        $cReportKey = [];
        foreach ($data as $val) {
    
            if (!isset($bodyReference[$val->id]['total_cost_basis'])) {
                $bodyReference[$val->id]['total_cost_basis'] = 0;
            }
            $bodyReference[$val->id]['total_cost_basis'] += round($val->cost_basis);

            if (!isset($bodyReference[$val->id]['c' . round($val->$report_key)])) {
                $bodyReference[$val->id]['c' . round($val->$report_key)] = 0;
                $cReportKey['c' . round($val->$report_key)] = true;
            }

            $bodyReference[$val->id]['c' . round($val->$report_key)] += 1;
            $bodyReference[$val->id]['key'] = $val->id;
            if (!isset($bodyReference[$val->id]['total_redemption_value'])) {
                $bodyReference[$val->id]['total_redemption_value'] = 0;
            }
            $bodyReference[$val->id]['total_redemption_value'] += round($val->$report_key, 2);


            if (!isset($bodyReference[$val->id]['total_premium'])) {
                $bodyReference[$val->id]['total_premium'] = 0;
            }
            $bodyReference[$val->id]['total_premium'] = $val->redemption_value - $val->sku_value;

            $bodyReference[$val->id]['percent_total_redemption_value'] = 0;

            $bodyReference[$val->id]['percent_total_cost'] = 0;

            $bodyReference[$val->id]['avg_discount_percent'] = round((($val->redemption_value - $val->cost_basis) / $val->redemption_value) * 100, 2);
            $bodyReference[$val->id]['name'] = $val->name;
            if (!isset($this->cardSum[round($val->$report_key)])) {
                $this->cardSum[round($val->$report_key)] = 0;
            }
            $this->cardSum[round($val->$report_key)] += 1;

        }

        foreach ($bodyReference as $key => $val) {

            if (!isset($this->total['total_redemption_value'])) {
                $this->total['total_redemption_value'] = 0;
            }
            $this->total['total_redemption_value'] += $val['total_redemption_value'];

            if (!isset($this->total['total_premium'])) {
                $this->total['total_premium'] = 0;
            }
            $this->total['total_premium'] += $val['total_premium'];

            if (!isset($this->total['total_cost_basis'])) {
                $this->total['total_cost_basis'] = 0;
            }
            $this->total['total_cost_basis'] += $val['total_cost_basis'];

            if (!isset($this->total['avg_discount_percent'])) {
                $this->total['avg_discount_percent'] = 0;
            }
            $this->total['avg_discount_percent'] += $val['avg_discount_percent'];
        }
        if (isset($this->total['avg_discount_percent'])) {
            $this->total['avg_discount_percent'] = round($this->total['avg_discount_percent'] / count($bodyReference), 2);
        } else {
            $this->total['avg_discount_percent'] = 0;
        }


        foreach ($bodyReference as $key => $val) {
            $bodyReference[$key]['percent_total_redemption_value'] = round(($val['total_redemption_value'] * 100) / $this->total['total_redemption_value'], 2);
            $bodyReference[$key]['percent_total_cost'] = round(($val['total_cost_basis'] * 100) / $this->total['total_cost_basis'], 2);

            foreach ( $cReportKey as $rkey => $rval )
            {
                if ( !isset($bodyReference[$key][$rkey]) )                
                    $bodyReference[$key][$rkey] = 0;                
            }            
        }

        foreach ($bodyReference as $key => $val) {
            if (!isset($this->total['percent_total_redemption_value'])) {
                $this->total['percent_total_redemption_value'] = 0;
            }
            $this->total['percent_total_redemption_value'] += $val['percent_total_redemption_value'];

            if (!isset($this->total['percent_total_cost'])) {
                $this->total['percent_total_cost'] = 0;
            }
            $this->total['percent_total_cost'] += $val['percent_total_cost'];
        }

        $colCard = [];
        foreach ($this->cardSum as $key => $val) {
            $colCard['c'.$key] = $val;
        }

        $total = array_merge($colCard, $this->total,['key'=>'total']);

        if (isset($total['percent_total_cost'])) {
            $total['percent_total_cost'] = round($total['percent_total_cost']);
        }

        if (isset($total['percent_total_redemption_value'])) {
            $total['percent_total_redemption_value'] = round($total['percent_total_redemption_value'], 2);
        }

        $merchants = Merchant::get()->toTree();
        $merchants =  _tree_flatten($merchants);

        $newTable = [];
        foreach ($merchants as $key => $item) {
            if (empty($item->dinamicPath) && isset($bodyReference[$item->id])) {
                $newTable[$item->id] = $bodyReference[$item->id];
            } else {
                $tmpPath = explode(',', $item->dinamicPath);
                if (isset($newTable[$tmpPath[0]]) && isset($bodyReference[$item->id])) {
                    if (empty($newTable[$tmpPath[0]]['childrenCount']))
                        $newTable[$tmpPath[0]]['childrenCount'] = 1;
                    else
                        $newTable[$tmpPath[0]]['childrenCount'] ++;
                    foreach($bodyReference[$item->id] as $subKey => $subItem){
                        if($subKey != 'key' && $subKey !== 'name'){
                            if(empty($newTable[$tmpPath[0]][$subKey])){
                                $newTable[$tmpPath[0]][$subKey] = $subItem;
                            }
                            else{
                                $newTable[$tmpPath[0]][$subKey] += $subItem;
                            }
                        }
                    }
                    $newTable[$tmpPath[0]]['percent_total_cost'] = round($newTable[$tmpPath[0]]['percent_total_cost'], 2);
                    $newTable[$tmpPath[0]]['percent_total_redemption_value'] = round($newTable[$tmpPath[0]]['percent_total_redemption_value'], 2);
                }
            }
        }
        foreach($newTable as $key => $item){
            if(isset($item['childrenCount'])){
                $newTable[$key]['avg_discount_percent'] = round( $item['avg_discount_percent'] / ($item['childrenCount'] + 1 ), 2);
            }
        }

        return [
            'data' => $newTable,
            'total' => count($newTable),
            'config' => [
                'columns' => $this->getHeaders(),
                'total' => $total
            ]
        ];
    }

    protected function getReportForCSV(): array
    {
        return [];
    }

    public function getHeaders(): array
    {
        $cardSum = $this->cardSum;
        $headers = [];
        $headers[] = [
            'label' => 'Merchant',
            'fixed' => true,
            'key' => 'name',
            'dataIndex' => 'name',
            'footer' => "Total",
            'width' => 200,
            'prefix' => "",
            'suffix' => "",
            'type' => "string",
            'title' => "Merchant",
        ];
        ksort($cardSum);
        foreach ($cardSum as $key => $val) {
            $headers[] = [
                'label' => "$key",
                'key' => "c$key",
                'dataIndex' => "c$key",
                'title' => "$key",
                'fixed' => false,
                'footer' => "$val",
                'width' => 70,
                'prefix' => "",
                'suffix' => "",
                'type' => "integer",
            ];
        }
        $headers[] = [
            'label' => 'Total Redemption Value ($)',
            'key' => 'total_redemption_value',
            'dataIndex' => 'total_redemption_value',
            'title' => "Total Redemption Value ($)",
            'fixed' => false,
            'footer' => $this->total['total_redemption_value'] ?? '0',
            'width' => 200,
            'prefix' => "$",
            'suffix' => "",
            'type' => "float",
        ];
        $headers[] = [
            'label' => 'Total Premium ($)',
            'key' => 'total_premium',
            'dataIndex' => 'total_premium',
            'title' => "Total Premium ($)",
            'fixed' => false,
            'footer' => $this->total['total_premium'] ?? '0',
            'width' => 180,
            'prefix' => "$",
            'suffix' => "",
            'type' => "float",
        ];
        $headers[] = [
            'label' => 'Percent Total Redemption Value (%)',
            'key' => 'percent_total_redemption_value',
            'dataIndex' => 'percent_total_redemption_value',
            'title' => "Percent Total Redemption Value (%)",
            'fixed' => false,
            'footer' => round($this->total['percent_total_redemption_value'] ?? 0),
            'width' => 250,
            'prefix' => '%',
            'suffix' => "%",
            'type' => "float",
        ];
        $headers[] = [
            'label' => 'Total Cost ($)',
            'key' => 'total_cost_basis',
            'dataIndex' => 'total_cost_basis',
            'title' => "Total Cost ($)",
            'fixed' => false,
            'footer' => $this->total['total_cost_basis'] ?? '0',
            'width' => 180,
            'prefix' => "$",
            'suffix' => "",
            'type' => "float",
        ];
        $headers[] = [
            'label' => 'Percent Total Cost (%)',
            'title' => 'Percent Total Cost (%)',
            'key' => 'percent_total_cost',
            'dataIndex' => 'percent_total_cost',
            'fixed' => false,
            'footer' => round($this->total['percent_total_cost'] ?? 0),
            'width' => 180,
            'prefix' => "",
            'suffix' => "%",
            'type' => "float",
        ];
        $headers[] = [
            'label' => 'Average Discount (%)',
            'title' => 'Average Discount (%)',
            'key' => 'avg_discount_percent',
            'dataIndex' => 'avg_discount_percent',
            'fixed' => false,
            'footer' => $this->total['avg_discount_percent'] ?? 0,
            'width' => 180,
            'prefix' => "",
            'suffix' => "%",
            'type' => "float",
        ];

        return $headers;
    }
}
