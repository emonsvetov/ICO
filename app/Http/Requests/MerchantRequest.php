<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MerchantRequest extends FormRequest
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
            'description' => 'required|string',
            'merchant_code' => 'required|string',
            'redemption_instruction' => 'required|string',
            'logo' => 'sometimes|image|mimes:jpeg,png,jpg,gif,ico|max:2048',
            'icon' => 'sometimes|image|mimes:jpeg,png,jpg,gif,ico|max:2048',
            'large_icon' => 'sometimes|image|mimes:jpeg,png,jpg,gif,ico|max:2048',
            'banner' => 'sometimes|image|mimes:jpeg,png,jpg,gif,ico|max:2048',
            'website' => 'required|string',
            'redemption_callback_id' => 'nullable',
            'category' => 'nullable|string',
            'website_is_redemption_url' => 'nullable',
            'get_gift_codes_from_root' => 'nullable',
            'is_default' => 'sometimes|nullable',
            'giftcodes_require_pin' => 'nullable',
            'display_rank_by_priority' => 'nullable',
            'display_rank_by_redemptions' => 'nullable',
            'requires_shipping' => 'nullable',
            'physical_order' => 'nullable',
            'is_premium' => 'nullable',
            'use_tango_api' => 'nullable',
            'toa_id' => 'nullable',
            'status' => 'nullable',
            'display_popup' => 'nullable',
            'deleted' => 'nullable'
        ];
    }
}
