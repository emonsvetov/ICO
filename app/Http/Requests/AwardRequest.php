<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AwardRequest extends FormRequest
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
            'email_template_id' => 'required|integer',
            'event_id' => 'required|integer',
            'message' => 'required|string',
            'notes' => 'nullable|string',
            'override_cash_value' => 'nullable|numeric',
            'referer' => 'nullable|string',
            'documentationFile' => [
                'nullable',
                'file',
                'mimes:pdf,txt,doc,docx'
            ],
            'user_id' => 'required|array',
            'user_id.*' => 'required|integer',
        ];
    }
}
