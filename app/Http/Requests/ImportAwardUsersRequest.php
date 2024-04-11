<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportAwardUsersRequest extends FormRequest
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
            'program_id' => 'required|integer',
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|email',
            'event_id' => 'required|integer',
            'override_cash_value' => 'nullable|numeric',
            'referrer' => 'nullable|string',
            'message' => 'required|string',
            'notes' => 'nullable|string'
        ];
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
