<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProgramMerchantRequest extends FormRequest
{
    protected function prepareForValidation()
    {
    }
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
            'merchant_id' => 'required|integer',
            'featured' => 'sometimes|boolean',
            'cost_to_program' => 'sometimes|boolean'
        ];
    }

    public function messages()
    {
        return [
            'merchant_id.required' => 'Merchant is required'
        ];
    }
}