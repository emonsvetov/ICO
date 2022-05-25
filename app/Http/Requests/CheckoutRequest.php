<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CheckoutRequest extends FormRequest
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
            'items' => 'required|array',
            'items.*.merchant_id' => 'required|integer',
            'items.*.redemption_value' => 'required|numeric',
            'items.*.qty' => 'required|integer',
        ];
    }
}
