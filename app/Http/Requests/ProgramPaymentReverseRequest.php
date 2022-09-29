<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\Decimal82;

class ProgramPaymentReverseRequest extends FormRequest
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
            'journal_event_id'=> 'required|integer',
            'event_type'=> 'required|string',
            'notes'=> 'required|string',
            'amount'=> ['required', new Decimal82()],
        ];
    }
}
