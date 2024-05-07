<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CsvImportTypeFieldsRequest extends FormRequest
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
            'fields' => 'required|array',
            'fields.*.id' => 'integer|nullable',
            'fields.*.name' => 'required|string',
            'fields.*.rule' => 'required|string',
        ];
    }
}
