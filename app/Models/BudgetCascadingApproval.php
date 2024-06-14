<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BudgetCascadingApproval extends Model
{
    use HasFactory;
    protected $fillable = [
        'parent_id', // Add this line
        'awarder_id',
        'user_id',
        'requestor_id',
        'manager_id',
        'event_id',
        'award_id',
        'program_approval_id',
        'amount',
        'approved',
        'award_data',
        'transaction_id',
        'program_id',
        'include_in_budget',
        'budgets_cascading_id',
        'action_by',
        'scheduled_date',
    ];

    public function program()
    {
        return $this->belongsTo(Program::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class);
    }

    public function requestor()
    {
        return $this->belongsTo(User::class, 'requestor_id');
    }

    // Define the relationship for user
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function budget_cascading()
    {
        return $this->belongsTo(BudgetCascading::class, 'budgets_cascading_id');
    }
}
