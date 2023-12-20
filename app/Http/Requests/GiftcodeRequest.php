<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GiftcodeRequest extends FormRequest
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
            'purchase_date' => 'required|date',
            'redemption_value' => 'required|numeric',
            'cost_basis' => 'required|numeric',
            'discount' => 'required|numeric',
            'sku_value' => 'required|numeric',
            'code' => 'required|unique:medium_info',
            'pin' => 'string|nullable',
            'redemption_url' => 'required|string',
        ];
    }
}
