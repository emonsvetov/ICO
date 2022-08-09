<?php

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\BaseModel;

class Invoice extends BaseModel
{
    use SoftDeletes;
    
    protected $guarded = [];
    public $timestamps = true;
    const DAYS_TO_PAY = 45;
    public function type()    {
        return $this->belongsTo(InvoiceType::class);
    }
    public function journal_events()
    {
        return $this->belongsToMany(JournalEvent::class)->withTimestamps();
    }
}