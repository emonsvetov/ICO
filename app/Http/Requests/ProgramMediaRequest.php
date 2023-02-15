<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProgramMediaRequest extends FormRequest
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
            // TODO: add to upload rules
//            'file' => 'required|mimes:jpeg,png,jpg,gif,ico,pdf,doc,docx,mp4,3gpp|max:10240',
//            'icon' => 'required|mimes:jpeg,png,jpg,gif,ico|max:2048',
            'name' => 'required|string',
            'mediaType' => 'required|integer',
            'file' => 'required|string',
            'icon' => 'required|string',
        ];
    }

}
