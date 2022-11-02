<?php

namespace App\Http\Requests;

use Illuminate\Validation\Factory as ValidationFactory;
use Illuminate\Foundation\Http\FormRequest;
use App\Rules\Decimal82;
use App\Models\Account;

class ProgramTransferMoniesRequest extends FormRequest
{
    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $invalid_programs = false;
            $programs = $this->program->whereIn('id', array_keys($this->amounts))->select( 'organization_id')->get();
            foreach($programs as $program)  {
                if ( $program->organization_id != $this->program->organization_id) 
                {
                    $validator->errors()->add('programs', 'Invalid programs found in the request');
                    $invalid_programs = true;
                    break;
                }
            }
            if( !$invalid_programs )    {
                $balance = Account::read_available_balance_for_program ( $this->program );
                if(array_sum($this->amounts) >= $balance)   {
                    $validator->errors()->add('balance', 'Insufficient balance to transfer monies');
                }
            }
        });
    }
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
            'amounts'=> 'required|array',
            'amounts.*'=> ['required', new Decimal82()]
        ];
    }
}
