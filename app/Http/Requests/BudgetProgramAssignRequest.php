<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BudgetProgramAssignRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'budget_type' => 'required|integer',
            'budget_amount' => 'required|array',
            'remaining_budgets' => 'numeric',
            'external_ids' => 'required|array',
            'program_budget_amounts' => 'required|array',
            'program_budgets_ids.*' => 'required|integer',
        ];
    }
}
