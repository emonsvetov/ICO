<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetCascading extends Model
{
    use HasFactory;
    protected $table = 'budgets_cascading';
    protected $primaryKey = 'budgets_cascading_id';

    protected $fillable = [
        'sub_program_external_id',
        'budget_amount',
        'budget_amount_remaining',
        'parent_program_id',
        'program_id',
        'program_budget_id',
        'budget_start_date',
        'budget_end_date',
        'created_by',
        'updated_by',
        'reason_for_budget_change'
    ];

    public static function budgetCascadingList()
    {
        return self::all();
    }
}
