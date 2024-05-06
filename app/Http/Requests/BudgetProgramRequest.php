<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
         return [
            'budget_type_id' => 'required|integer',
            'budget_amount' => 'numeric',
            'remaining_amount' => 'numeric',
            'budget_start_date' => 'date',
            'budget_end_date' => 'date',
        ];
    }
}
