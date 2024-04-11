<?php

namespace App\Services\reports;

use App\Models\MediumInfo;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class ReportOrdersService extends ReportServiceAbstract
{

    /**
     * @inheritDoc
     */
    protected function getBaseQuery(): Builder
    {
        $query = DB::table('medium_info');

        $query->leftJoin('users', 'users.id', '=', 'medium_info.redeemed_user_id');
        $query->leftJoin('merchants as redeemed_merchants', 'redeemed_merchants.id', '=', 'medium_info.redeemed_merchant_id');
        $query->leftJoin('merchants', 'merchants.id', '=', 'medium_info.merchant_id');
        $query->leftJoin('programs', 'programs.id', '=', 'medium_info.redeemed_program_id');

        $query->selectRaw("
        medium_info.*,
        users.email as user_email,
        CONCAT(users.first_name, ' ', users.last_name) as redeemed_by_user_name,
        users.id as redeemed_user_id,
        merchants.name as redeemed_merchant_name,
        merchants.id as merchant_id,
        redeemed_merchants.id as redeemed_merchant_id
        programs.name as redeemed_program_name,
        programs.id as redeemed_program_id
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
