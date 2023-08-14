<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\Decimal82;

class ProgramDepositRequest extends FormRequest
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
            'notes'=> 'sometimes|string',
            'invoice_id' => 'sometimes|integer',
            'payment_kind'=> 'required|string',
            'request_type'=> 'required|in:init,settlement',
            'amount'=> ['required', new Decimal82()],
            'hash'=> 'sometimes|string',
        ];
    }
}
