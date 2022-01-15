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
            'logo' => 'required|image|mimes:jpeg,png,jpg,gif,ico|max:2048',
            'icon' => 'required|image|mimes:jpeg,png,jpg,gif,ico|max:2048',
            'large_icon' => 'nullable|image|mimes:jpeg,png,jpg,gif,ico|max:2048',
            'banner' => 'nullable|image|mimes:jpeg,png,jpg,gif,ico|max:2048',
            'website' => 'string|required',
            'redemption_callback_id' => 'integer|nullable',
            'category' => 'string|nullable',
            'website_is_redemption_url' => 'boolean|nullable',
            'get_gift_codes_from_root' => 'boolean|nullable',
            'is_default' => 'boolean|nullable',
            'giftcodes_require_pin' => 'boolean|nullable',
            'display_rank_by_priority' => 'integer|nullable',
            'display_rank_by_redemptions' => 'integer|nullable',
            'requires_shipping' => 'boolean|nullable',
            'physical_order' => 'boolean|nullable',
            'is_premium' => 'boolean|nullable',
            'use_tango_api' => 'boolean|nullable',
            'toa_id' => 'integer|nullable',
            'status' => 'integer|nullable',
            'display_popup' => 'boolean|nullable',
            'deleted' => 'boolean|nullable'
        ];
    }
}
