<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use App\Models\BudgetProgram;
use App\Models\Program;

class UniqueBudgetDuration implements Rule
{
    protected $programId;
    protected $budgetTypeId;
    protected $startDate;
    protected $endDate;

    public function __construct($programId, $budgetTypeId, $startDate, $endDate)
    {
        $this->programId = $programId;
        $this->budgetTypeId = $budgetTypeId;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function passes($attribute, $value)
    {
        $p_program = new Program();
        $programId = $p_program->get_top_level_program_id($this->programId);
        $existingBudget = BudgetProgram::where('budget_start_date', '<=', $this->endDate)
            ->where('budget_end_date', '>=', $this->startDate)
            ->where('budget_type_id', $this->budgetTypeId)
            ->where('program_id', $programId)
            ->exists();

        return !$existingBudget;
    }

    public function message()
    {
        return 'A budget already exists for the selected duration.';
    }
}
