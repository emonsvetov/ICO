<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AccountPostingRequest extends FormRequest
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
            'debit_account_holder_id' => 'required|integer',
            'debit_account_type_id' => 'required|integer',
            'debit_finance_type_id' => 'required|integer',
            'debit_medium_type_id' => 'required|integer',
            'credit_account_holder_id' => 'required|integer',
            'credit_account_type_id' => 'required|integer',
            'credit_finance_type_id' => 'required|integer',
            'credit_medium_type_id' => 'required|integer',
            'journal_event_id' => 'required|integer',
            'amount' => 'required|regex:/^\d*(\.\d{2})?$/',
            'medium_fields' => 'nullable',
            'medium_values' => 'nullable',
            'currency_type_id' => 'required|integer',
        ];
    }
}
