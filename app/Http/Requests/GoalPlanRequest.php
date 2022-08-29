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
            'next_goal_id'=>'integer',
            'previous_goal_id'=>'integer', 
            'program_id'=>'required|integer',
            'organization_id'=>'required|integer',
            'name'=>'required|string',
            'goal_measurement_label'=>'required|string',
            'goal_plan_type_id'=>'required|integer',
            'state_type_id'=>'required|integer',
            'default_target'=>'required|numeric',
            //'email_template_id'=>'integer',
            'notification_body'=>'string',
            'achieved_callback_id'=>'string',
            'exceeded_callback_id'=>'string',
            'achieved_event_id'=>'sometimes|integer',
            'exceeded_event_id'=>'required_if:goal_plan_type_id,1',//|integer', //required if sales goal (id-1)
            'automatic_progress'=> 'required|boolean',
            'automatic_frequency'=>'required_if:automatic_progress,1',//|string',
            'automatic_value'=>'required_if:automatic_progress,1',//|integer',
            'expiration_rule_id'=>'required|integer',
            'custom_expire_offset'=>'required_if:expiration_rule_id,4',//|integer', //if expiration_rule_id is custom
            'custom_expire_units'=>'required_if:expiration_rule_id,4',//|string', //if expiration_rule_id is custom 
            'annual_expire_month'=>'required_if:expiration_rule_id,5',//|integer', //if expiration_rule_id is annual
            'annual_expire_day'=> 'required_if:expiration_rule_id,5',//|integer',  //if expiration_rule_id is annual integer
            'date_begin'=> 'required|date_format:Y-m-d',
            'date_end'=>'required_if:expiration_rule_id,6|date_format:Y-m-d|after:date_begin', //if expiration_rule_id is specific date
            'factor_before'=>'required_if:goal_plan_type_id,1',//|numeric',
            'factor_after'=>'required_if:goal_plan_type_id,1',//|numeric',
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
     /**
          * Get the validation messages that apply to the request.
          *
          * @return array
          */
         /* public function messages()
          {
              return [
                //'exceeded_event_id.required' =>"The exceeded event id field is required when goal plan type is 'Sales Goal'",
                /*'oldpassword.required' => Lang::get('userpasschange.oldpasswordrequired'),
                'oldpassword.max' => Lang::get('userpasschange.oldpasswordmax255'),
                'newpassword.required' => Lang::get('userpasschange.newpasswordrequired'),
                'newpassword.min' => Lang::get('userpasschange.newpasswordmin6'),
                'newpassword.max' => Lang::get('userpasschange.newpasswordmax255'),
                'newpassword.alpha_num' =>Lang::get('userpasschange.newpasswordalpha_num'),
                'newpasswordagain.required' => Lang::get('userpasschange.newpasswordagainrequired'),
                'newpasswordagain.same:newpassword' => Lang::get('userpasschange.newpasswordagainsamenewpassword'),
                'username.max' => 'The :attribute field must  have under 255 chars',*/
             // ];
          //}
}