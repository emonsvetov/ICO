<?php

namespace App\Http\Requests;

use Illuminate\Validation\Factory as ValidationFactory;
use \Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Foundation\Http\FormRequest;
use App\Rules\CsvContent;

class MerchantGiftcodeRequest extends FormRequest
{
    public function __construct(ValidationFactory $validationFactory)
    {
        $validationFactory->extend(
            'csv_match_merchant_code',
            function ($attribute, $value, $parameters) {
                return $value == $this->merchant->merchant_code;
            },
            ':attribute does not match'
        );
    }

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
            'file_medium_info' => [
                'bail',
                'file',
                'mimes:csv,txt,xls,xlsx',
                new CsvContent( [
                    'purchase_date' => 'required|date',
                    'supplier_code' => ['required', 'csv_match_merchant_code'],
                    'redemption_value' => 'required|integer',
                    'cost_basis' => 'required|integer',
                    'discount' => 'required|integer',
                    'sku_value' => 'required|integer',
                    'code' => 'required|string',
                    'pin' => 'required|string',
                    'redemption_url' => ' required|string',
                ])
            ]
        ];
    }
    
    // protected function failedValidation( $validator)
    // {
    //     $response = response([
    //         'errors' => $validator->errors()
    //     ]);
    //     throw (new ValidationException($validator, $response))
    //     ->errorBag($this->errorBag)
    //     ->redirectTo($this->getRedirectUrl());
    // }
}