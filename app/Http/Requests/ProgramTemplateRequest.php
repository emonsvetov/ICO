<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProgramTemplateRequest extends FormRequest
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
            'small_logo' => 'sometimes|image|mimes:jpeg,png,jpg,gif,ico|max:2048',
            'big_logo' => 'sometimes|image|mimes:jpeg,png,jpg,gif,ico|max:2048',
            'hero_banner' => 'sometimes|image|mimes:jpeg,png,jpg,gif,ico|max:2048',
            'slider_01' => 'sometimes|image|mimes:jpeg,png,jpg,gif,ico|max:2048',
            'slider_02' => 'sometimes|image|mimes:jpeg,png,jpg,gif,ico|max:2048',
            'slider_03' => 'sometimes|image|mimes:jpeg,png,jpg,gif,ico|max:2048',
            'welcome_message' => 'string|nullable',
            'is_active' => 'boolean|nullable'
        ];
    }
}
