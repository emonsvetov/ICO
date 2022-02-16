<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Rules\Decimal82;

class DomainAddProgramRequest extends FormRequest
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
            'program_id' => 'required|integer'
        ];
    }

    public function messages()
    {
        return [
            'program_id.required' => 'Program is required'
        ];
    }
}