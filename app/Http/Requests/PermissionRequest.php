<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PermissionRequest extends FormRequest
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
            'roles' => 'nullable|array',
            'roles.*' => 'integer', // check each item in the array
        ];
        
    }

    public function messages()
    {
        return [
            'roles' => 'Invalid roles format',
            'roles.*' => 'Invalid roles format',
        ];
    }
}
