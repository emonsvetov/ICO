<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UserGoalRequest extends FormRequest
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
            'user_id'=>'integer',
            'goal_plan_id'=>'integer',
            'next_user_goal_id'=>'integer', 
            'previous_user_goal_id'=>'integer',
            'achieved_callback_id'=>'string',
            'exceeded_callback_id'=>'string',
            // $table->timestamp('date_met')->nullable();
            //$table->timestamp('date_exceeded')->nullable();
            'factor_before'=>'numeric',
            'factor_after'=>'numeric',
            'calc_progress_total'=>'numeric',
            'calc_progress_percentage'=>'numeric',
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