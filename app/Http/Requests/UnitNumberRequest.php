<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UnitNumberRequest extends FormRequest
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
            'description'=> 'required|string'
        ];
    }

    public function importRules()
    {
        return [
            'name' => 'required|string',
            'description'=> 'required|string',
            'program_id'=> 'mustExistInModel:Program|use:id|hide:true|provided:true'
        ];
    }
}
