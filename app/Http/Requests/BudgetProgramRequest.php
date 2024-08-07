<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\UniqueBudgetDuration;

class BudgetProgramRequest extends FormRequest
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
        $programId = $this->route('program')->id;
        $budgetTypeId = $this->input('budget_type_id');
        $startDate = $this->input('budget_start_date');
        $endDate = $this->input('budget_end_date');

        return [
            'budget_type_id' => 'required|integer',
            'budget_amount' => 'numeric|min:1',
            'remaining_amount' => 'numeric',
            'budget_start_date' => ['required', 'date'],
            'budget_end_date' => ['required', 'date', new UniqueBudgetDuration($programId, $budgetTypeId, $startDate, $endDate)],
        ];
    }
}
