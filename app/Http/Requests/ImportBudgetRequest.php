<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportBudgetRequest extends FormRequest
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

    public function rules()
    {
        return [
            'file' => 'required|mimes:csv,txt|max:2048',
        ];
    }

    public function messages()
    {
        return [
            'file.required' => 'A CSV file is required.',
            'file.mimes' => 'Only CSV files are allowed.',
            'file.max' => 'File size must be under 2MB.',
        ];
    }
}
