<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProgramApprovalRequest extends FormRequest
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
            'program_id' => 'array',
            // 'step' => 'integer',
            // 'position_level_id' => 'required|array',
            // 'position_level_id.*' => 'required|integer',
            'approval_request' => 'array',
        ];
    }
}
