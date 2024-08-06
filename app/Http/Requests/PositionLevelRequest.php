<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\UniqueTitle;

class PositionLevelRequest extends FormRequest
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
        $programId = $this->route('program')->id;
        return [
            'title' => ['required', 'string', new UniqueTitle($programId)],
            'name' => 'string',
            'level' => 'integer',
            'status' => 'boolean',
        ];
    }
}
