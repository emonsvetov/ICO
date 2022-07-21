<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GoalPlanRequest extends FormRequest
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
           // 'name' => ['required|string', Rule::unique('goal_plans', 'name')->ignore($this->goal_plans)],
            'name'=>'required|string',
            'goal_plan_type_id'=>'required|integer',
            'event_id'=>'required|integer',
            'program_id'=>'required|integer',
            'organization_id'=>'required|integer',
            'automatic_progress'=> 'required|boolean',
            'automatic_frequency'=>'sometimes|string',
            'automatic_value'=>'sometimes|integer',
            'start_date'=> 'required|date_format:Y-m-d',
            'default_target'=>'required|numeric',
            'goal_measurement_label'=>'required|string',
            'factor_before'=>'sometimes|numeric',
            'factor_after'=>'sometimes|numeric',
            'expiration_rule_id'=>'required|integer',
            'annual_expire_month'=>'sometimes|integer', //if expiration_rule_id is annual
            'annual_expire_day'=> 'sometimes|integer',  //if expiration_rule_id is annual
            'custom_expire_offset'=>'sometimes|integer', //if expiration_rule_id is custom
            'custom_expire_units'=>'sometimes|string', //if expiration_rule_id is custom 
            'expire_date'=>'sometimes|date_format:Y-m-d',
            'achieved_event_id '=>'sometimes|integer',
            'exceeded_event_id '=>'sometimes|integer',
            'progress_email_template_id'=>'required|integer',
            'is_recurring'=>'sometimes|boolean',
            'award_per_progress'=>'sometimes|boolean',
            'award_email_per_progress'=>'sometimes|boolean',
            'progress_requires_unique_ref_num'=>'sometimes|boolean',
            'assign_goal_all_participants_default'=>'sometimes|boolean',
        ];
    }
}