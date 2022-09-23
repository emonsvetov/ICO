<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class JournalEventRequest extends FormRequest
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
            'prime_account_holder_id' => 'required|integer',
            'journal_event_type_id' => 'required|integer',
            'notes' => 'nullable|string',
            'event_xml_data_id' => 'nullable|integer',
            'invoice_id' => 'nullable|integer',
            'parent_id' => 'nullable|integer',
            'is_read' => 'nullable|boolean',
        ];
    }
}
