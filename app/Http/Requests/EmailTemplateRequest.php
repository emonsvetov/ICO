<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EmailTemplateRequest extends FormRequest
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
            'name' => 'required|string',
            'content'=> 'required|string',
            'type'=> 'required|string',
            'is_default'=> 'boolean|nullable',
            'organization_id'=> 'required|integer',
            'program_id'=> 'required|integer'
        ];
    }

    public function importRules()
    {
        return [];
    }
}
