<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EventIconRequest extends FormRequest
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
            // 'icon' => 'required',
            // 'icon.*' => 'mimes:png,jpeg,jpg,ico | max:2048',
            'icon' => 'mimes:png,jpeg,jpg,ico | max:2048',
        ];
    }
}
