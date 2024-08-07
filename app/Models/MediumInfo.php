<?php

namespace App\Models;

use App\Models\Traits\Treeable;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class MediumInfo extends BaseModel
{
    use Treeable;

    protected $guarded = [];
    protected $table = 'medium_info';

    const MEDIUM_TYPE_STATUS_SUCCESS = 1;
    const MEDIUM_TYPE_STATUS_ERROR = 2;

    public function newQuery()
    {
        $query = parent::newQuery();

        $query->where('purchased_by_v2', '=', 0);

        return $query;
    }

    public function postings()
    {
        return $this->hasOne(Posting::class, 'medium_info_id');
    }

    public function merchant()
    {
        return $this->hasOne(Merchant::class, 'id', 'merchant_id');
    }

    public static function isTest()
    {
        return app()->environment('production') ? false : true;
    }

    public static function getSkuValues()
    {
        return self::select(['sku_value'])
            ->where('sku_value', '>', 0)
            ->orderBy('sku_value', 'ASC')
            ->groupBy('sku_value')
            ->get()
            ->pluck('sku_value')
            ->toArray();
    }

    /**
     * Reads the list or count of gift codes that are redeemable from the given merchant
     * Note: If the merchant gets its gift codes from it's parent merchant, this will return the redeemable gift codes
     * from this merchant's parent.
     *
     * @param int $merchantId
     * @param string $endDate
     * @return Collection
     */
    public static function getRedeemableDenominationsByMerchant(int $merchantId = 0, $endDate = FALSE, $extraArgs = []): Collection
    {

        // Retrieve merchant details
        $merchant = Merchant::where('id', $merchantId)->first();
        if ($merchant->get_gift_codes_from_root) {
            $rootMerchant = $merchant->getRoot();
            $merchantId = (int)$rootMerchant->id;
        } else {
            $merchantId = (int)$merchant->id;
        }

        // Start constructing the query
        $query = MediumInfo::select(
            'merchant_id as merchant_id',
            DB::raw('FORMAT(redemption_value, 2) as redemption_value'),
            DB::raw('FORMAT(sku_value, 2) as sku_value'),
            'virtual_inventory',
            DB::raw('COUNT(medium_info.id) as count'),
            DB::raw('SUM(case when virtual_inventory = 1 then 1 else 0 end) as count_virtual_inventory'),
            DB::raw('SUM(case when virtual_inventory = 0 then 1 else 0 end) as count_real_inventory')
        )
            ->where('merchant_id', $merchantId);

        // Apply conditions based on extraArgs
        $inventoryType = $extraArgs['inventoryType'] ?? FALSE;
        if ($inventoryType) {
            $inventoryTypes = [
                1 => 0,
                2 => 1
            ];
            $query->where('medium_info.virtual_inventory', $inventoryTypes[$inventoryType]);
        }

        if ($endDate) {
            $query->where('purchase_date', '<=', $endDate);
            $query->where(function($q) use ($endDate) {
                $q->orWhere('redemption_date', null)
                    ->orWhere('redemption_date', '>', $endDate);
            });
        }else{
            $query->whereNull('redemption_date');
        }

        // Group by and order by
        $query->groupBy('sku_value')
            ->orderBy('sku_value', 'ASC');

        return $query->get();
    }


    public static function getListRedeemedByParticipant(int $userId, bool $obfuscate = true, int $offset = 0, int $limit = 10)
    {
        $query = MediumInfo::with('merchant')
            ->select(
                DB::raw("
                medium_info.*
            "));
        if($obfuscate){
            $query->addSelect(
                DB::raw("upper(substring(MD5(RAND()), 1, 20)) as `code`")
            );
        }
        $query->where('redeemed_user_id', '=', $userId);
        $query->orderBy('redemption_date', 'DESC');

        try {
            return $limit ? $query->limit($limit)->offset($offset)->get() : $query->get();
        } catch (\Exception $e) {
            throw new \Exception('DB query failed.', 500);
        }
    }

    /**
     * Calculates the cost basis of the current inventory of a single merchant
     *
     * @param int $merchantId
     * @return float
     */
    public static function getCostBasis(int $merchantId, $params = [])
    {
        $inventoryType = $params['inventoryType'] ?? FALSE;
        $endDate = $params['endDate'] ?? FALSE;
        $totalCost = MediumInfo::select(
//            ->join('postings', 'postings.medium_info_id', '=', 'medium_info.id')
//            ->join('accounts', 'accounts.id', '=', 'postings.account_id')
            DB::raw('SUM(medium_info.cost_basis) as cost_basis')
        )->where('medium_info.merchant_id', '=', $merchantId);


        if ($endDate) {
            $totalCost->where('purchase_date', '<=', $endDate);
            $totalCost->where(function($query) use ($endDate) {
                $query->orWhere('redemption_date', null)
                    ->orWhere('redemption_date', '>', $endDate);
            });
        }else{
            $totalCost->whereNull('redemption_date');
        }

        if ($inventoryType) {
            $totalCost->where('virtual_inventory', [1 => 0, 2 => 1][$inventoryType]);
        }

        $totalCost = $totalCost->get();

        $finalTotalCost = $totalCost->isEmpty() ? 0 : $totalCost->first()->cost_basis;

        return $finalTotalCost;
    }

    /**
     * @param $ID
     * @return mixed
     */
    public static function getByID($ID)
    {
        $query = DB::table('medium_info');
        $query->where('id', $ID);
        $query->selectRaw("medium_info.*");
        return $query->get()->first();
    }
}
