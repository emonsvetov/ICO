<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class EventXmlDataRequest extends FormRequest
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
            'awarder_account_holder_id' => 'required|integer',
            'name' => 'required|string',
            'award_level_name' => 'required|string',
            'amount_override' => 'nullable|integer',
            'notification_body' => 'nullable|string',
            'notes' => 'nullable|string',
            'referrer' => 'nullable|string',
            'email_template_id' => 'nullable|integer',
            'event_type_id' => 'nullable|integer',
            'event_template_id' => 'nullable|integer',
            'icon' => 'nullable',
            'award_transaction_id' => 'nullable|string',
            'lease_number' => 'nullable|string',
            'token' => 'nullable|string',
            'created_at' => 'nullable|timestamp',
            'updated_at' => 'nullable|timestamp',
        ];
    }
}
