<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\BaseModel;

class Invoice extends BaseModel
{
    use SoftDeletes;
    
    protected $guarded = [];
    public $timestamps = true;

    public function type()    {
        return $this->belongsTo(InvoiceType::class);
    }
}