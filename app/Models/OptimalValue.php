<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OptimalValue extends Model
{
    use HasFactory;

    protected $guarded = [];
    protected $table = 'merchant_optimal_values';

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public function readByMerchanIdAndDenomination( $merchant_id, $denomination) {
        return self::where(['merchant_id' => $merchant_id, 'denomination' => $denomination])
        ->get();
    }

    public static function getByMerchantId(int $merchantId)
    {
        return self::where(['merchant_id' => $merchantId])->get();
    }

}
