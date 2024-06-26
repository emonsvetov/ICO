<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\Decimal82;

class AnetCreditCardPaymentRequest extends FormRequest
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
            'card_number' =>  'exclude_if:payment_type,bank|required|integer|digits_between:13,16', //("4111111111111111"); 13-16 digits
            'expiration_date' => 'exclude_if:payment_type,bank|required|date_format:Y-m', //("2038-12");
            'card_code' => 'exclude_if:payment_type,bank|required|integer|digits:3', //("123");

            'first_name' => 'required|max:50',
            'last_name' => 'required|max:50',
            'company' => 'nullable|max:50',
            'address' => 'nullable|max:60',
            'city' => 'nullable|max:40',
            'state' => 'nullable|max:40',
            'zip' => 'nullable|max:20',
            'country' => 'nullable|max:2|min:2',

            'amount'=> ['required', new Decimal82()],
        ];
    }
}
