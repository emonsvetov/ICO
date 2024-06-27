<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\Decimal82;

class AnetPayPalPaymentRequest extends FormRequest
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
            'amount'=> ['required_without_all:payerID,refTransId', new Decimal82()],
            'redirectUrl' => 'required_with:amount',
            'payerID' => 'required_without:amount',
            'refTransId' => 'required_without:amount',            
        ];
    }
}
