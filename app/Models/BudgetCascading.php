<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetCascading extends Model
{
    use HasFactory;
    protected $table = 'budgets_cascading';
    protected $primaryKey = 'id';

    protected $fillable = [
        'budget_program_id',
        'program_id',
        'parent_program_id',
        'program_external_id',
        'employee_count',
        'budget_percentage',
        'budget_amount',
        'budget_awaiting_approval',
        'budget_amount_remaining',
        'budget_start_date',
        'budget_end_date',
        'flag',
        'status',
        'reason_for_budget_change'
    ];

    public static function budgetCascadingList()
    {
        return self::all();
    }
}
