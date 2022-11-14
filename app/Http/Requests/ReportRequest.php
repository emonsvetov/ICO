<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReportRequest extends FormRequest
{
    public $reportTypes = [
        'inventory'
    ];
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
            'merchant_ids' => 'nullable|array',
            'merchant_ids.*' => 'required|integer',
            'end_date' => 'nullable|date_format:Y-m-d',
            'report_type' => 'required|in:' . implode(',', $this->reportTypes),
        ];
    }
}
