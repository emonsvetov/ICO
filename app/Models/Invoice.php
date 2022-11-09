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

    const PROGRAM_PAYMENT_KINDS = [
        "program_pays_for_points" => "Program Pays for Points",
        "program_pays_for_setup_fee" => "Program Pays for Setup Fee",
        "program_pays_for_admin_fee" => "Program Pays for Admin Fee",
        "program_pays_for_usage_fee" => "Program Pays for Usage Fee",
        "program_pays_for_deposit_fee" => "Program Pays for Deposit Fee",
        "program_pays_for_fixed_fee" => "Program Pays for Fixed Fee",
        "program_pays_for_convenience_fee" => "Program Pays for Convenience Fee",
        "program_pays_for_monies_pending" => "Program Pays for Monies Pending",
        "program_pays_for_points_transaction_fee" => "Program Pays for Points Transaction Fee",
        "program_refunds_for_monies_pending" => "Program Refunds for Monies Pending"
    ];

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

    public static function getProgramMonthlyInvoice4Date($program, $date_begin)
    {
        return self::where('program_id', $program->id)
        ->where('date_begin', 'LIKE', $date_begin)
        ->whereHas('invoice_type', function($query) {
            $query->where('name', 'LIKE', 'Monthly');
        })->get();
    }
}