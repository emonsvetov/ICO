<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PostingRequest extends FormRequest
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
            'journal_event_id' => 'required|integer',
            'debit_account_id' => 'nullable|integer',
            'credit_account_id' => 'nullable|integer',
            'posting_amount' => 'required|regex:/^\d*(\.\d{4})?$/',
            'qty' => 'required|regex:/^\d*(\.\d{4})?$/',
            'medium_fields' => 'nullable',
            'medium_values' => 'nullable',
            'medium_info_id' => 'nullable|integer',
        ];
    }
}
