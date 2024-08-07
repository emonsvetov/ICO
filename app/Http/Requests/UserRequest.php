<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserRequest extends FormRequest
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
            'user_status_id' => 'nullable|integer',
            'first_name' => 'required|string',
            'last_name' =>  'required|string',
            'email' => [
                "required", //when using array format each rule should be applied individualy..
                "email:filter", // E.g. supplying "required|email:filter" wont work here!
                Rule::unique('users', 'email')->ignore($this->user)
            ],
            'email_verified_at' => 'nullable',
            'password' => 'sometimes|confirmed|string',
            'organization_id' => 'sometimes|integer',
            'phone' => 'nullable|string|max:50',
            'award_level' => 'nullable|integer',
            'work_anniversary' => 'nullable|date',
            'dob' => 'nullable|date',
            'username' => 'nullable|string',
            'employee_number' => 'nullable|integer',
            'division' => 'nullable|string',
            'office_location' => 'nullable|string',
            'position_level' => 'nullable|integer',
            'position_grade_level' => 'nullable|string',
            'supervisor_employee_number' => 'nullable|integer',
            'organizational_head_employee_number' => 'nullable|integer',
            'deactivated' => 'nullable|date_format:Y-m-d H:i:s',
            'activated' => 'nullable|date_format:Y-m-d H:i:s',
            'state_updated' => 'nullable|date_format:Y-m-d H:i:s',
            'last_location' => 'nullable|string',
            'update_id' => 'nullable|integer',
            'roles' => 'sometimes|required|array', // program specific roles
            'roles.*' => 'sometimes|required|integer',
            'role' => 'sometimes|string',
            'avatar' => 'sometimes|nullable|image|mimes:jpeg,png,jpg,gif,ico|max:2048',
            'send_invite' => 'sometimes|boolean',
            'external_id' => 'nullable|string',
            'unit_number' => 'sometimes|nullable|integer',
            'is_organization_admin' => 'sometimes|boolean',
        ];
    }

    public function importRules()
    {
        return [
            'user_id' => 'mustComeFromModel:User|matchWith:id|use:id',
            'user_status_id' => 'mustComeFromModel:Status|matchWith:status|use:id|filter:context,=,Users',
            'organization_id' => 'mustExistInModel:Organization|use:id|hide:true|provided:true',
            'update_id' => 'mustExistInModel:User|use:id|hide:true|provided:true',
            'work_anniversary' => 'nullable|date_format:Y-m-d',
            'dob' => 'nullable|date_format:Y-m-d',
            'external_id' => 'nullable|string',
            'division' => 'hide:true',
            'award_level' => 'hide:true',
            'organizational_head_employee_number' => 'provided:programatically',
            'roles' => 'mustComeFromModel:Role|matchWith:name|use:id|filterConstant:organization_id,=,organization_id|filterOrNull:organization_id|dataType:array',
            'roles.*' => 'hide:true',
            'email' => 'hideByImportType:true'
        ];
    }

    public function setups()
    {
        return [
            'type' => 'required|integer',
            'roles' => 'required|array', // only 1 role per user in the csv import
            'mail' => 'nullable|boolean',
            'status' => 'nullable|integer',
        ];
    }

    public function importSetups()
    {
        return [
            'type' => 'mustComeFromModel:CsvImportType|matchWith:name|use:id|filter:context,=,Users',
            'roles' => 'mustComeFromModel:Role|matchWith:name|use:id|filterConstant:organization_id,=,organization_id|filterOrNull:organization_id|dataType:array',
            'mail' => 'nullable|boolean',
            'status' => 'nullable|mustComeFromModel:Status|matchWith:status|use:id|filter:context,=,Users',
        ];
    }

    public function attributes()
    {
        return [
            'dob' => 'Birthday',
        ];
    }
}
