<?php

namespace App\Models;

use App\Models\BaseModel;

class PaymentMethod extends BaseModel
{
    protected $guarded = [];
    public $timestamps = true;

    public function get_payment_method_ach() {
    }
}
