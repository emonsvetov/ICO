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
            'user_status_id' => 'nullable|integer',
            'first_name' => 'required|string',
			'last_name' =>  'required|string',
            'email' => 'required|email|unique:users',
            'email_verified_at'=>'nullable',
            'password'=>'required|string',
			'phone'=> 'nullable|string|max:50',
			'award_level'=> 'required|string',
			'work_anniversary'=> 'nullable|date',
			'dob'=> 'nullable|date',
			'username'=> 'nullable|string',
            'employee_number'=> 'nullable|integer',
			'division'=> 'nullable|string',
			'office_location'=> 'nullable|string',
			'position_title'=> 'nullable|string',
			'position_grade_level'=> 'nullable|string',
			'supervisor_employee_number'=> 'nullable|integer',
			'organizational_head_employee_number'=> 'nullable|integer',
            'deactivated'=> 'nullable|date_format:Y-m-d H:i:s',
            'activated'=> 'nullable|date_format:Y-m-d H:i:s',
            'state_updated'=> 'nullable|date_format:Y-m-d H:i:s',
            'last_location'=> 'nullable|string',
            'update_id'=> 'nullable|integer',
        ];
    }
}