<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserProgramRequest extends FormRequest
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
            'program_id' => 'required|integer',
            'roles' => 'required|array',
            'roles.*' => 'required|integer',
        ];
    }

    public function messages()
    {
        return [
            'program_id.required' => 'Program is required'
        ];
    }
}
