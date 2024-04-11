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
            'redemption_callback_id' => 'sometimes|integer',
            'category' => 'nullable|string',
            'website_is_redemption_url' => 'sometimes|boolean',
            'get_gift_codes_from_root' => 'sometimes|boolean',
            'is_default' => 'sometimes|boolean',
            'giftcodes_require_pin' => 'sometimes|boolean',
            'display_rank_by_priority' => 'sometimes|integer',
            'display_rank_by_redemptions' => 'sometimes|integer',
            'requires_shipping' => 'sometimes|boolean',
            'physical_order' => 'sometimes|boolean',
            'is_premium' => 'sometimes|boolean',
            'use_tango_api' => 'sometimes|boolean',
            'use_virtual_inventory' => 'sometimes|boolean',
            'virtual_denominations' => 'sometimes|string',
            'virtual_discount' => 'sometimes|string',
            'toa_id' => 'sometimes|integer',
            'status' => 'sometimes|integer',
            'display_popup' => 'sometimes|boolean',
            'deleted' => 'sometimes|boolean',
            'v2_merchant_id' => 'sometimes|integer',
            'set_second_email_from_tango' => 'sometimes|integer'
        ];
    }
}
