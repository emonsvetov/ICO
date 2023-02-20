<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReclaimPeerPointsRequest extends FormRequest
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
            'reclaim' => 'required|array',
            'reclaim.*.id' => 'required|integer',
            'reclaim.*.note' => 'nullable|string',
            'reclaim.*.journal_event_id' => 'required|integer',
            'reclaim.*.amount' => 'required|numeric',
        ];
    }
}