<?php

namespace App\Models;

use App\Models\Traits\Treeable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class MediumInfo extends BaseModel
{
    use Treeable;

    protected $guarded = [];
    protected $table = 'medium_info';

    public function postings()
    {
        return $this->hasOne(Posting::class, 'medium_info_id');
    }

    public function merchant()
    {
        return $this->hasOne(Merchant::class, 'account_holder_id', 'merchant_id');
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
    public static function getRedeemableDenominationsByMerchant(int $merchantId, string $endDate = ''): Collection
    {
        /**
         * check to see if the merchant gets its gift codes from the root merchant
         * if it does, query using the root merchant id instead
         */
        $merchant = Merchant::where('account_holder_id', $merchantId)->first();
        $merchantId = (int)$merchant->account_holder_id;
        if ($merchant->get_gift_codes_from_root) {
            $rootMerchant = $merchant->getRoot();
            $merchantId = (int)$rootMerchant->account_holder_id;
        }

        $query = MediumInfo::select(
            DB::raw("
                max(`merchant_id`) as merchant_id,
                `redemption_value`,
                `sku_value`,
                `redemption_value` - `sku_value` as `redemption_fee`,
                 COUNT(DISTINCT `medium_info`.`id`) as count
            "))
            ->join('postings', 'medium_info.id', '=', 'postings.medium_info_id')
            ->join('accounts', 'accounts.id', '=', 'postings.account_id');

        if (self::isTest()) {
            $query->where('medium_info_is_test', '=', 1);
        }
        if ($endDate) {
            $query->where('purchase_date', '<=', $endDate)
                ->where(function ($query) use ($endDate) {
                    return $query
                        ->whereNull('redemption_date')
                        ->orWhere('redemption_date', '>', $endDate);
                });
        } else {
            $query->whereNull('redemption_date');
        }

        $query->where('merchant_id', '=', $merchantId)
//            ->where('hold_until', '<=', 'now()')
            ->groupBy('sku_value', 'redemption_value')
            ->orderBy('sku_value', 'ASC')
            ->orderBy('redemption_value', 'ASC');

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
    public static function getCostBasis(int $merchantId): float
    {

        $query = DB::table(function ($subQuery) use ($merchantId) {
            $subQuery->select('cost_basis', 'merchant_id')
                ->from('medium_info')
                ->join('postings', 'postings.medium_info_id', '=', 'medium_info.id')
                ->join('accounts', 'accounts.id', '=', 'postings.account_id')
                ->where('merchant_id', '=', $merchantId);;
            }, 'subQuery')
            ->select(
                DB::raw("
                    COALESCE(SUM(cost_basis), 0) AS total_cost
                ")
            );


        $result = $query->get();
        return $result ? (float)$result[0]->total_cost : 0.00;
    }

}
