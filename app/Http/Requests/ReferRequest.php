<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReferRequest extends FormRequest
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
            'sender_first_name' => 'required|string',
            'sender_last_name' => 'required|string',
            'sender_email' => 'required|email',
            'category_referral'=>'integer|nullable',
            'category_feedback'=>'integer|nullable',
            'category_lead'=>'integer|nullable',
            'category_reward'=>'integer|nullable',
            'reward_amount'=>'float|nullable',
            'recipient_first_name' => 'nullable|string',
            'recipient_last_name' => 'nullable|string',
            'recipient_email' => [
                "nullable",
                "email:filter"
            ],
            'message' => 'required|string'
        ];
    }
}
