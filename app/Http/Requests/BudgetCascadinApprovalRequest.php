<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BudgetCascadinApprovalRequest extends FormRequest
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
            'budget_cascading_approval_id' => 'required|array',
            'budget_cascading_approval_id.*' => 'required|integer',
            'approved' => 'integer',
            'scheduled_date' => 'nullable|string',
            'rejection_note'=>'nullable|string'
        ];
    }
}
