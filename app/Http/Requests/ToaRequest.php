<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ToaRequest extends FormRequest
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
            'name' => 'required|string',
            'platform_name' => 'required|string',
            'platform_key' => 'required|string',
            'platform_url' => 'required|string',
            'platform_mode' => 'required|string',
            'account_identifier' => 'required|string',
            'account_number' => 'required|string',
            'customer_number' => 'required|string',
            'udid' => 'required|string',
            'etid' => 'required|string',
            'status' => 'required|boolean',
            'is_test' => 'required|boolean',
            'toa_merchant_min_value' => 'required|numeric',
            'toa_merchant_max_value' => 'required|numeric'
        ];
    }
}
