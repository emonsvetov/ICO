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
            'goal_plan_id'=>'required|integer',
            'target_value'=>'required|numeric|gt:0',
            'next_user_goal_id'=>'integer|nullable', 
            'previous_user_goal_id'=>'integer|nullable',
            'achieved_callback_id'=>'integer|nullable',
            'exceeded_callback_id'=>'integer|nullable',
            'date_met'=>'nullable',
            'date_exceeded'=>'nullable',
            'factor_before'=>'numeric|nullable|min:0',
            'factor_after'=>'numeric|nullable|min:0',
            'calc_progress_total'=>'numeric|nullable',
            'calc_progress_percentage'=>'numeric|nullable',
            'date_begin'=> 'required|date_format:Y-m-d',
            'date_end'=>'required|date_format:Y-m-d|after:date_begin',
             'created_by'=>'integer',
             'modified_by'=>'integer|nullable',
             'expired'=>'nullable',
        ];
    }
}