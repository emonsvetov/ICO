<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LeaderboardRequest extends FormRequest
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
            'leaderboard_type_id' => 'required|integer',
            'status_id' => 'sometimes|integer',
            'enable' => 'sometimes|boolean',
            'visible' => 'sometimes|boolean',
            'one_leaderboard' => 'sometimes|boolean',
        ];
    }
}
