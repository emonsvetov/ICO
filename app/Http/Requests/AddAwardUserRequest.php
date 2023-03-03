<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddAwardUserRequest extends FormRequest
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
            'first_name' => 'required|string',
			'last_name' =>  'required|string',
            
        ];
    }


    public function importRules()
    {
        return [
            'user_status_id' => 'mustComeFromModel:Status|matchWith:status|use:id|filter:context,=,Users',
            'organization_id'=> 'mustExistInModel:Organization|use:id|hide:true|provided:true',
            'update_id'=> 'mustExistInModel:User|use:id|hide:true|provided:true',
            'work_anniversary'=> 'nullable|date_format:Y-m-d',
			'dob'=> 'nullable|date_format:Y-m-d',
            'division'=> 'hide:true',
            'award_level'=> 'hide:true',
            'organizational_head_employee_number' => 'provided:programatically',
            'roles' => 'mustComeFromModel:Role|matchWith:name|use:id|filterConstant:organization_id,=,organization_id|filterOrNull:organization_id|dataType:array',
            // 'roles' => 'hide:true',
            'roles.*' => 'hide:true',
        ];
    }


    public function setups()
    {
        return [
            'type' => 'required|integer',
            'roles' => 'required|array', // only 1 role per user in the csv import
            'mail' => 'nullable|boolean',
        ];
    }

    public function importSetups()
    {
        return [
            'type' => 'mustComeFromModel:CsvImportType|matchWith:type|use:id|filter:context,=,Users',
            'roles' => 'mustComeFromModel:Role|matchWith:name|use:id|filterConstant:organization_id,=,organization_id|filterOrNull:organization_id|dataType:array',
            'mail' => 'nullable|boolean',
        ];
    }
}
