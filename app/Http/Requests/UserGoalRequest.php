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
            'user_id' => 'required|array',
            'user_id.*' => 'required|integer',
            'goal_plan_id'=>'integer',
            'next_user_goal_id'=>'integer', 
            'previous_user_goal_id'=>'integer',
            'achieved_callback_id'=>'string',
            'exceeded_callback_id'=>'string',
            //$table->timestamp('date_met')->nullable();
            //$table->timestamp('date_exceeded')->nullable();
            'factor_before'=>'numeric',
            'factor_after'=>'numeric',
            'calc_progress_total'=>'numeric',
            'calc_progress_percentage'=>'numeric',
            //'created_by'=>'required|integer',
            //'modified_by'=>'integer',
             'expired',
        ];
    }
}