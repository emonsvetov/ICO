<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReferralNotificationRecipientRequest extends FormRequest
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
            'referral_notification_recipient_email'=> 'required|string',
            'referral_notification_recipient_name'=> 'required|string',
            'referral_notification_recipient_lastname'=> 'required|string',
            'referral_notification_recipient_active'=> 'boolean',
        ];
    }
}
