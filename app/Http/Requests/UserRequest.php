<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
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
            'first_name' => 'required|string',
			'last_name' =>  'required|string',
            'email' => 'required|email|unique:users',
			'phone'=> 'nullable|string|max:50',
			'award_level'=> 'required|string',
			'work_anniversary'=> 'nullable|date',
			'dob'=> 'nullable|date',
			'username'=> 'nullable|string',
			'division'=> 'nullable|string',
			'office_location'=> 'nullable|string',
			'position_title'=> 'nullable|string',
			'position_grade_level'=> 'nullable|string',
			'supervisor_employee_number'=> 'nullable|integer',
			'organizational_head_employee_number'=> 'nullable|integer',
        ];
    }
}