<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RoleRequest extends FormRequest
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
            'permissions' => 'required|array',
            'permissions.*' => 'required|integer', // check each item in the array
        ];
        
    }

    public function messages()
    {
        return [
            'permissions.*' => 'Please select permissions',
        ];
    }
}
