<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReferralRequest extends FormRequest
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
            'sender_id' => 'required|integer',
            'recipient_first_name' => 'required|string',
            'recipient_last_name' => 'required|string',
            'recipient_email' => [
                "required",
                "email:filter"
            ],
            'recipient_area_code' => 'required|string',
            'recipient_phone' => 'required|string',
            'message' => 'required|string'
        ];
    }
}
