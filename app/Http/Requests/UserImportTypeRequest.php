<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserImportTypeRequest extends FormRequest
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
        return [];
    }

    public function importRules()
    {
        return [];
    }

    public function setups()
    {
        return [];
    }

    public function importSetups()
    {
        return [
            'type' => 'mustComeFromModel:CsvImportType|matchWith:type|use:id|filter:context,=,Users'
        ];
    }
}
