<?php

namespace App\Models;

use App\Models\BaseModel;

class InvoiceJournalEvent extends BaseModel
{
    protected $guarded = [];
    public $timestamps = true;
    public $table = 'invoice_journal_event';
}
