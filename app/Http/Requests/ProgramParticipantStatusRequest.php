<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProgramParticipantStatusRequest extends FormRequest
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
            //'program_id' => 'required|integer',
            'users' => 'required|array',
			'users.*' =>  'required|integer',
			'status' =>  'string|in:Pending Activation,Active,Deleted,Pending Deactivation,Deactivated,Locked,New'
        ];
    }
}