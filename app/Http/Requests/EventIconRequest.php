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
            'image' => 'required|array|max:5',
            'image.*' => 'required|image|mimes:jpeg,png,jpg,gif,ico|max:2048',
            'icon_upload_type' => 'sometimes|string|in:global,program'
        ];
    }

    public function attributes()
    {
        return [
            'image' => 'Image',
            'image.*' => 'Image',
        ];
    }
}
