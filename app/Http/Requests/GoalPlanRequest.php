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
    { $table->mediumText('notification_body')->nullable();
        $table->mediumText('achieved_callback_id')->nullable();
        $table->mediumText('exceeded_callback_id')->nullable();
        return [
           // 'name' => ['required|string', Rule::unique('goal_plans', 'name')->ignore($this->goal_plans)],
            'next_goal_id'=>'integer',
            'previous_goal_id'=>'integer', 
            'program_id'=>'required|integer',
            'organization_id'=>'required|integer',
            'name'=>'required|string',
            'goal_measurement_label'=>'required|string',
            'goal_plan_type_id'=>'required|integer',
            'state_type_id'=>'required|integer',
            'default_target'=>'required|numeric',
            'email_template_id'=>'integer',
            'notification_body'=>'string',
            'achieved_callback_id'=>'string',
            'exceeded_callback_id'=>'string',
            'achieved_event_id '=>'sometimes|integer',
            'exceeded_event_id '=>'sometimes|integer',
            'automatic_progress'=> 'required|boolean',
            'automatic_frequency'=>'sometimes|string',
            'automatic_value'=>'sometimes|integer',
            'expiration_rule_id'=>'required|integer',
            'custom_expire_offset'=>'sometimes|integer', //if expiration_rule_id is custom
            'custom_expire_units'=>'sometimes|string', //if expiration_rule_id is custom 
            'annual_expire_month'=>'sometimes|integer', //if expiration_rule_id is annual
            'annual_expire_day'=> 'sometimes|integer',  //if expiration_rule_id is annual
            'date_begin'=> 'required|date_format:Y-m-d',
            'date_end'=>'sometimes|date_format:Y-m-d',
            'factor_before'=>'sometimes|numeric',
            'factor_after'=>'sometimes|numeric',
            'is_recurring'=>'sometimes|boolean',
            'award_per_progress'=>'sometimes|boolean',
            'award_email_per_progress'=>'sometimes|boolean',
            'progress_requires_unique_ref_num'=>'sometimes|boolean',
            'progress_notification_email_id'=>'required|integer',
            //'progress_email_template_id'=>'required|integer',
            'assign_goal_all_participants_default'=>'sometimes|boolean',
            'created_by'=>'required|integer',
             'modified_by'=>'integer',
             'expired',
        ];
    }
}