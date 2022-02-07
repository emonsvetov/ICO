<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreMerchantProgramRequest extends FormRequest
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
            'merchant_id' => 'required|exists:App\Models\Merchant,id',
            'program_id' => 'required|exists:App\Models\Program,id',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array
     */
    public function messages()
    {
        return [
            'merchant_id.required' => 'Merchant id is required',
            'merchant_id.exists' => 'The selected merchant id is invalid.',
            'program_id.required' => 'Program id is required',
            'program_id.exists' => 'The selected program id is invalid.',
        ];
    }
}
