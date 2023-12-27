<?php

namespace App\Services\reports;

use App\Models\AccountType;
use App\Models\JournalEventType;
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

        $query->selectRaw("
            medium_info.*,
            CONCAT(users.first_name, ' ', users.last_name) as redeemed_by
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

        $search = $this->params[self::KEYWORD];
        if (!blank($search)) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'LIKE', "%$search%");
                $q->orWhereRaw("CONCAT(users.first_name, ' ', users.last_name) LIKE ?", ["%" . $search . "%"]);
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
