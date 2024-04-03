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
            'email_template_id' => 'nullable|integer', // TODO: email templates
            'event_id' => 'required|integer',
            'message' => 'required|string',
            'notes' => 'nullable|string',
            'override_cash_value' => 'nullable|numeric',
            'referrer' => 'nullable|string',
            'documentationFile' => [
                'nullable',
                'file',
                'mimes:pdf,txt,doc,docx'
            ],
            'user_id' => 'required|array',
            'user_id.*' => 'required|integer'
        ];
    }

    public function importRules()
    {
        return [
            'email_template_id' => 'mustComeFromModel:EmailTemplate|matchWith:name|use:id',
            'event_id'          => 'mustComeFromModel:Event|matchWith:id|use:name',
            'documentationFile' => 'hide:true',
            'user_id'=> 'override:true|required|integer',
            'user_id.*'         => 'hide:true',
        ];
    }

    public function setups()
    {
        return [
            'event_id' => 'nullable|integer',
        ];
    }

    public function importSetups()
    {
        return [
            'event_id' => 'nullable|mustComeFromModel:Event|matchWith:id|use:name|filterConstant:organization_id,=,organization_id|provided:true',
        ];
    }

}
