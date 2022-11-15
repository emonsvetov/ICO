<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\Decimal82;

class InvoicePaymentRequest extends FormRequest
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
            'notes'=> 'string|nullable',
            'applied_payments' => 'required|array',
            'applied_payments.*'=> 'required|array',
            'applied_payments.*.program_id'=> 'required|integer',
            // 'applied_payments.*.program_id'=> ['required', new Decimal82()],
        ];
    }
}
