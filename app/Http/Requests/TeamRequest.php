<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TeamRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'photo' => 'nullable|string',
            'name' => 'required|string',
            'title' => 'nullable|string',
            'description'=> 'nullable|string',
            'contact_phone'=> 'nullable|string',
            'contact_email'=> 'nullable|string',
            'deleted'=> 'boolean|nullable',
        ];
    }
} 