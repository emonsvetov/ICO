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
            'match_merchant_code',
            function ($attribute, $value, $parameters) {
                $file = $this->file('file_medium_info');
                $fileName = $file ? $file->getClientOriginalName() : '';
                return $fileName == 'SyncGifCodesFromV2.csv' ? true : $value == $this->merchant->merchant_code;
            },
            ':attribute does not match'
        );

        $validationFactory->extend(
            'giftcode_requires_pin',
            function ($attribute, $value, $parameters) {
                if ($this->merchant->giftcodes_require_pin && (! is_string ( $value ) || strlen ( trim ( $value ) ) < 1) ) {
                    return false;
                }
                return true;
            },
            ':attribute requires pin'
        );

        $validationFactory->extend(
            'is_valid_code',
            function ($attribute, $value, $parameters) {
                //TODO: check for implementation ; check is_valid_code($merchant_id, $code = '') in api/application/models/gift_codes_model.php
                // Current implementation requires binding of postings, accounts, account_types, with medium_info. Need to revisit when these relations are clear
                return true;
            },
            ':attribute is not valid'
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
                    'supplier_code' => ['required', 'match_merchant_code'],
                    'redemption_value' => 'required|numeric',
                    'cost_basis' => 'required|numeric',
                    'discount' => 'required|numeric',
                    'sku_value' => 'required|string',
                    'code' => 'required|is_valid_code', // we don't need unique for purchase process.
                    //'code' => 'required|is_valid_code|unique:medium_info',
                    'pin' => ['giftcode_requires_pin'],
                    'redemption_url' => 'string'
                ])
            ]
        ];
    }
}
