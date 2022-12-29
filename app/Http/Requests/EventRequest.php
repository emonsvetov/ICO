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
			'enable'=> 'sometimes|boolean|nullable',
			'event_type_id'=> 'required|numeric',
			'event_icon_id'=> 'sometimes|integer',
			'max_awardable_amount'=> ['required', new Decimal82()],
			'post_to_social_wall'=> 'nullable|boolean',
			'message'=> 'required|string',
			'include_in_budget'=> 'boolean|nullable',
			'enable_schedule_award'=> 'boolean|nullable',
			'is_birthday_award'=> 'boolean|nullable',
			'is_anniversary_award'=> 'boolean|nullable',
			'award_message_editable'=> 'boolean|nullable',
			'ledger_code'=> 'numeric|nullable',
            'email_template_id'=> 'sometimes|integer',
            'custom_email_template' =>'sometimes|boolean',
            'template_name'=> 'required_if:custom_email_template,true|string',
            'email_template'=> 'required_if:custom_email_template,true|string',
        ];
    }
    
    public function importRules()
    {
        return [
            'organization_id'=> 'mustExistInModel:Organization|use:id|hide:true|provided:true',
			'event_type_id'=> 'mustComeFromModel:EventType|matchWith:type|use:id',
			'event_icon_id'=> 'mustComeFromModel:EventIcon|matchWith:name|use:id|filterConstant:organization_id,=,organization_id',
			'email_template_id'=> 'mustComeFromModel:EmailTemplate|matchWith:name|use:id',
            'post_to_social_wall'=> 'required|boolean',
            'custom_email_template' =>'hide:true',
        ];
    }
}
