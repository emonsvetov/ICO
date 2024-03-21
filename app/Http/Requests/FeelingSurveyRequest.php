<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FeelingSurveyRequest extends FormRequest
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
            'feeling' => 'required|string',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => [
                "required",
                "email:filter"
                ],
            'comment' => 'nullable|string'
        ];
    }
}
