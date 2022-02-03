<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Rules\Decimal82;

class DomainIPRequest extends FormRequest
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
            'ip_address' => "required|ip"
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Domain IP Address is required',
            'name.ip' => 'A valid Domain IP Address is invalid',
        ];
    }
}