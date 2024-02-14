<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UsersLogRequest extends FormRequest
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
            'user_account_holder_id' => 'required|integer',
			'parent_program_id' =>  'required|nullable',
            'email' => 'required|email',
            'first_name' => 'required|nullable',
            'last_name' => 'required|nullable',
            'type'=> 'required|string',
            'old_user_status_id'=> 'nullable|integer',
            'new_user_status_id'=> 'required|integer',
            'updated_by'=> 'nullable|integer',
            'technical_reason_id'=> 'nullable|integer',
            'updated_at'=> 'nullable|string',
        ];
    }
}
