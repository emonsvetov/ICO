<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\Decimal82;

class AnetGooglePaymentRequest extends FormRequest
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
            'first_name' => 'nullable|max:50',
            'last_name' => 'nullable|max:50',
            'company' => 'nullable|max:50',
            'address' => 'nullable|max:60',
            'city' => 'nullable|max:40',
            'state' => 'nullable|max:40',
            'zip' => 'nullable|max:20',
            'country' => 'nullable|max:2|min:2',

            'opaqueData' => 'required',
            'amount'=> ['required', new Decimal82()],        
        ];
    }
}
