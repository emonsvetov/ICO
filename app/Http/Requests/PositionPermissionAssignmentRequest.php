<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PositionPermissionAssignmentRequest extends FormRequest
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
           // 'position_level_id ' => 'required|array',
           // 'position_level_id .*' => 'required|integer',
            'position_permission_id' => 'required|array',
            'position_permission_id.*' => 'required|integer',
        ];
    }
}
