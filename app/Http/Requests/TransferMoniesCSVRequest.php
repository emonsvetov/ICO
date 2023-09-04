<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class TransferMoniesCSVRequest extends FormRequest
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
            'upload-file' => 'required|file|mimes:csv,txt'
        ];
    }

    public function fromRules()
    {
        return [
            'Transfer_from_program_id'    => 'mustExistInModel:Program|matchWith:id|use:id|filter:organization_id,=,organization_id',
            'Transfer_from_program_external_id'   => 'mustExistInModel:Program|matchWith:external_id|use:external_id|filter:organization_id,=,organization_id',
            'Transfer_from_program_name'  => 'mustExistInModel:Program|matchWith:name|use:name|filter:organization_id,=,organization_id',
        ];
    }
}
