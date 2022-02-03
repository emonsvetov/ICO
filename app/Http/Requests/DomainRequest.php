<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\Decimal82;

class DomainRequest extends FormRequest
{
    protected function prepareForValidation()
    {
        $request = $this->all(); 
        if( !isset($request['secret_key']) )    { //creation of domain!
            $this->merge([
                'secret_key' => sha1( time() ), //TODO
            ]);
        }
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
            'name' => "required|regex:/^(?!\-)(?:(?:[a-zA-Z\d][a-zA-Z\d\-]{0,61})?[a-zA-Z\d]\.){1,126}(?!\d+)[a-zA-Z\d]{1,63}$/|unique:domains,name,{$this->domain->id}",
			'secret_key'=> 'sometimes|nullable|string'
        ];
    }

    public function messages()
    {
        return [
            'name.required' => 'Domain name is required',
            'name.regex' => 'Domain name is invalid',
            'name.unique' => 'Domain name already exists',
            'secret_key.required' => 'Secret Key is invalid',
        ];
    }
}