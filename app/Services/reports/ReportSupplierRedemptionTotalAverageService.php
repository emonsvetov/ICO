<?php

namespace App\Services\reports;

use App\Models\MediumInfo;
use App\Models\Merchant;
use App\Models\OptimalValue;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use PhpParser\Node\Expr\Cast\Object_;
use stdClass;

class ReportSupplierRedemptionTotalAverageService extends ReportServiceAbstract
{
    const FIELD_AVG_DISCOUNT_PERCENT = 'avg_discount_percent';


    protected function getBaseSql(): Builder
    {
        $query = DB::table('merchants');
        $query->join('medium_info', 'medium_info.merchant_id', '=', 'merchants.id');
        $query->addSelect(
            DB::raw("
                COALESCE(AVG(((redemption_value - cost_basis) / redemption_value)),0) as " . self::FIELD_AVG_DISCOUNT_PERCENT . "
            "),
        );
        return $query;
    }

    /**
     * @inheritDoc
     */
    protected function setWhereFilters(Builder $query): Builder
    {
        $query->whereNotNull('medium_info.redemption_date');
        $query->whereIn('merchant_id', $this->params[self::MERCHANTS]);
        $query->where('redemption_value', '>', 'cost_basis');
        if ($this->params [self::MERCHANTS_ACTIVE]) {
            $query->where('merchants.status', '=', 1);
        }
        $query->whereBetween('medium_info.redemption_date',
            [$this->params[self::DATE_FROM], $this->params[self::DATE_TO]]);
        return $query;
    }
}
