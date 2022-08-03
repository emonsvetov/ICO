<?php

namespace App\Http\Requests;

use App\Models\SocialWallPostType;
use Illuminate\Foundation\Http\FormRequest;

class SocialWallPostRequest extends FormRequest
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
            'social_wall_post_type_id' => 'required|integer',
            'social_wall_post_id' => 'nullable|integer',
            'event_xml_data_id' => $this->social_wall_post_type_id === SocialWallPostType::TYPE_EVENT ? 'required|integer' : 'nullable|integer',
            'program_account_holder_id' => 'required|integer',
            'awarder_program_id' => 'nullable|integer',
            'sender_user_account_holder_id' => 'required|integer',
            'receiver_user_account_holder_id' => 'required|integer',
            'comment' => 'nullable|string',
            'updated_by' => 'nullable|integer',
        ];
    }
}
