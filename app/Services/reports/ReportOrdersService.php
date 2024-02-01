<?php

namespace App\Services\reports;

use App\Models\MediumInfo;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReportOrdersService extends ReportServiceAbstract
{

    /**
     * @inheritDoc
     */
    protected function getBaseQuery(): Builder
    {
        $query = DB::table('medium_info');

        $query->leftJoin('users', 'users.id', '=', 'medium_info.redeemed_user_id');

        $query->selectRaw("
            medium_info.*,
            CONCAT(users.email) as redeemed_by
        ");
        return $query;
    }

    /**
     * @inheritDoc
     */
    protected function setWhereFilters(Builder $query): Builder
    {
        $query->whereNotNull('redemption_date');
        $query->whereBetween('redemption_date', [$this->params[self::DATE_BEGIN], $this->params[self::DATE_END]]);

        $merchants = $this->params[self::MERCHANTS];
        if (!blank($merchants)) {
            $query->whereIn('merchant_id', $this->params[self::MERCHANTS]);
        }

        if ($this->params[self::ORDER_STATUS]) {
            $query->where('virtual_inventory', '=', 1);

            switch ($this->params[self::ORDER_STATUS]) {
                case MediumInfo::MEDIUM_TYPE_STATUS_SUCCESS:
                    $query->whereNotNull('tango_reference_order_id');
                    break;

                case MediumInfo::MEDIUM_TYPE_STATUS_ERROR:
                    $query->whereNull('tango_reference_order_id');
                    $query->whereNotNull('tango_request_id');
                    break;
            }
        }

        $purchaseByV2 = $this->params[self::PURCHASE_BY_V2];
        if ($purchaseByV2) {
            $query->where('purchased_by_v2', '=', [1 => 0, 2 => 1][$purchaseByV2]);
        }


        $search = $this->params[self::KEYWORD];
        if (!blank($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'LIKE', "%$search%");
                $q->orWhereRaw("CONCAT(users.email) LIKE ?", ["%" . $search . "%"]);
                $q->orWhere('sku_value', '=', $search);
                foreach ([
                    'pin',
                    'redemption_url',
                    'tango_reference_order_id',
                         ] as $field) {
                    $q->orWhere($field, 'LIKE', "%$search%");
                }
            });
        }

        $inventoryType = $this->params[self::INVENTORY_TYPE];
        if ($inventoryType) {
            $query->where('virtual_inventory', [1 => 0, 2 => 1][$inventoryType]);
        }
        return $query;
    }

}
