<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

use App\Models\Traits\CsvImport;

class CSVImportAwardUsersRequest extends FormRequest
{
    use CsvImport;
    // public array $rules = [
    //     'program_id' => 'required|integer',
    //     'first_name' => 'required|string',
    //     'last_name' => 'required|string',
    //     'email' => 'required|email',
    //     'event_id' => 'required|integer',
    //     'override_cash_value' => 'nullable|numeric',
    //     'referrer' => 'nullable|string',
    //     'message' => 'required|string',
    //     'notes' => 'nullable|string'
    // ];
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
        return $this->getRules();
    }

    public function importRules()
    {
        return [
            'event_id' => 'mustComeFromModel:Event|matchWith:name|use:id|filterConstant:organization_id,=,organization_id',
            'program_id' => 'mustComeFromModel:Program|matchWith:id|use:id|filterConstant:organization_id,=,organization_id',
        ];
    }

    public function setups()
    {
        return [];
    }

    public function importSetups()
    {
        return [
            'event_id' => 'mustComeFromModel:Event|matchWith:name|use:id|filterConstant:organization_id,=,organization_id',
            'program_id' => 'mustComeFromModel:Program|matchWith:name|use:id|filterConstant:organization_id,=,organization_id',
        ];
    }
}
