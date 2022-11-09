<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CronInvoice extends Model
{
    use HasFactory;
    public $timestamps = true;

    protected $guarded = [];

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public static function getProgramsToPostCharges()  
    {
        return CronInvoice::select(['id', 'program_id', 'name'])
        ->whereNull('charges_posted_date')
        ->orderBy('created_at', 'ASC')
        ->with(['program' => function($q) {
            $q->select('id', 'account_holder_id', 'name', 'create_invoices', 'administrative_fee', 'administrative_fee_calculation', 'fixed_fee', 'administrative_fee_factor', 'monthly_usage_fee');
        }])->get();
    }

    public static function getProgramsToInvoice()
    {
        return CronInvoice::select(['id', 'program_id', 'name'])
        ->whereNull('invoice_date')
        ->whereNotNull('charges_posted_date')
        ->orderBy('created_at', 'ASC')
        ->orderBy('id', 'ASC')
        ->with(['program' => function($q) {
            $q->select('id', 'parent_id', 'account_holder_id', 'name', 'create_invoices', 'administrative_fee', 'administrative_fee_calculation', 'fixed_fee', 'administrative_fee_factor', 'monthly_usage_fee', 'bill_parent_program', 'accounts_receivable_email');
        }])->limit(50)
        ->get();
    }
}
