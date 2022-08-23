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
    protected $appends = ['invoice_number'];

    protected function getInvoiceNumberAttribute()
    {
        return "{$this->key}-{$this->seq}";
    }
    public function invoice_type()    {
        return $this->belongsTo(InvoiceType::class);
    }
    public function program()    {
        return $this->belongsTo(Program::class);
    }
    public function journal_events()
    {
        return $this->belongsToMany(JournalEvent::class)->withTimestamps();
    }
}