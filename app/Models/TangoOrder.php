<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\PhysicalOrder;

class TangoOrder extends Model
{
    use HasFactory;
    protected $guarded = [];

    CONST ORDER_STATUS_NEW = 1;
    CONST ORDER_STATUS_PROCESS = 2;
    CONST ORDER_STATUS_SUCCESS = 3;
    CONST ORDER_STATUS_ERROR = 5;

    public function physical_order()
    {
        return $this->belongsTo(PhysicalOrder::class);
    }

    public function merchant()
    {
        return $this->belongsTo(Merchant::class);
    }

    public static function read_not_submitted_orders()
    {
        return self::where('status', self::ORDER_STATUS_NEW)
        ->with(['physical_order'])
        ->get();
    }    
}
