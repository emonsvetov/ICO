<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetProgram extends Model
{
    use HasFactory;
    protected $guarded = [];
    public $timestamp = true;

    public function budget_types()
    {
        return $this->belongsTo(BudgetType::class, 'budget_type_id');
    }

    /*protected $fillable = [
        'budget_type_id',
        'program_id ',
        'budget_amount',
        'remaining_amount',
        'budget_start_date',
        'budget_end_date',
        'status',
    ];*/
}
