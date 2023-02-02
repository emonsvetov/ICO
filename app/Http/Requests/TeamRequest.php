<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TeamRequest extends FormRequest
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
            'name' => 'required|string',
            'title' => 'required|string',
            'description'=> 'nullable|string',
            'contact_phone'=> 'required|string',
            'contact_email'=> 'required|string',
            'photo' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif,ico|max:2048',
        ];
    }
} 