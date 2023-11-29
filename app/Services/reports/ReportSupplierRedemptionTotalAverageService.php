<?php

namespace App\Services\reports;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ReportSupplierRedemptionTotalAverageService extends ReportServiceAbstract
{
    const FIELD_AVG_DISCOUNT_PERCENT = 'avg_discount_percent';

    protected function getBaseQuery(): Builder
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

        if (!empty($this->params['merchants'])) {
            $query->whereIn('merchants.id', $this->params['merchants']);
        }

        if (!empty($this->params['active'])) {
            $query->where('merchants.status', $this->params['active']);
        }

        if (!empty($this->params['codes'])) {
            $query->where('medium_info.virtual_inventory', $this->params['codes']);
        }

        if (!empty($this->params['dateFrom']) && !empty($this->params['dateTo'])) {
            $query->whereBetween('medium_info.redemption_datetime', [$this->params['dateFrom'], $this->params['dateTo']]);
        }

        return $query;
    }
}
