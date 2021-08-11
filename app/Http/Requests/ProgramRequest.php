<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProgramRequest extends FormRequest
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
            'program_id'            => 'nullable|integer',
            'name'                  => 'required|string',
            'setup_fee'             => 'required|numeric',
            'is_pay_in_advance'     => 'required|boolean',
            'is_invoice_for_rewards'=> 'required|boolean',
            'is_add_default_merchants'=> 'required|boolean',
            
        ];
    }
}
