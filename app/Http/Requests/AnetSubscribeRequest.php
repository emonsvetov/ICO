<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AnetSubscribeRequest extends FormRequest
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
            'subscription_type'=> 'required|in:monthly,annually',
            'payment_type'=> 'required|in:creditcard,bank',

            'card_number' =>  'exclude_if:payment_type,bank|required|integer|digits_between:13,16', //("4111111111111111"); 13-16 digits
            'expiration_date' => 'exclude_if:payment_type,bank|required|date_format:Y-m', //("2038-12");
            'card_code' => 'exclude_if:payment_type,bank|required|integer|digits:3', //("123");

            'account_type' => 'exclude_if:payment_type,creditcard|required|in:checking,savings,businessChecking', //('checking'); //Either checking, savings, or businessChecking.        
            'routing_number' => 'exclude_if:payment_type,creditcard|required|digits_between:1,9', //('122000661');
            'account_number' => 'exclude_if:payment_type,creditcard|required|integer|digits_between:1,17', //(rand(10000,999999999999));
            'name_on_account' => 'exclude_if:payment_type,creditcard|required|max:22', //('John Doe');
            'bank_name' => 'exclude_if:payment_type,creditcard|required|max:55',

            'first_name' => 'required|max:50',
            'last_name' => 'required|max:50',
            'company' => 'nullable|max:50',
            'address' => 'nullable|max:60',
            'city' => 'nullable|max:40',
            'state' => 'nullable|max:40',
            'zip' => 'nullable|max:20',
            'country' => 'nullable|max:2|min:2',

        ];
    }
}
