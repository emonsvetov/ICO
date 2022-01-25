<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\Decimal82;

class EventRequest extends FormRequest
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
            'name' => 'required|string',
			'enable'=> 'boolean|nullable',
			'type_id'=> 'required|numeric', //dropdown pending
			'event_icon_id'=> 'required|integer',
			'amount'=> ['required', new Decimal82()],
			'allow_amount_overriding'=> 'nullable|boolean',
			'email_template_id'=> 'required|integer', //dropdown pending
			'post_to_social_wall'=> 'nullable|boolean',
			'message'=> 'required|string',
			'include_in_budget'=> 'boolean|nullable',
			'enable_schedule_award'=> 'boolean|nullable',
			'is_birthday_award'=> 'boolean|nullable',
			'is_anniversary_award'=> 'boolean|nullable',
			'award_message_editable'=> 'boolean|nullable',
			'ledger_code'=> 'numeric|nullable',
        ];
    }
}