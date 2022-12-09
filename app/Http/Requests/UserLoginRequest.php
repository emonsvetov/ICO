<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

use App\Services\DomainService;
use App\Models\User;

class UserLoginRequest extends FormRequest
{
    public function __construct(DomainService $domainService)
    {
        $this->domainService = $domainService;
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

    // public function __validateDomainRequest()
    // {
    //     return $this->domainService->validateDomainRequest();
    // }

    // public function withValidator($validator)
    // {
    //     $validator->after(function ($validator) {
    //         try {
    //             if( !$this->__validateDomainRequest() )
    //             {
    //                 $validator->errors()->add('validationError', 'Invalid domain or account');
    //             }
    //         } catch (\Exception $e) {
    //             $validator->errors()->add('validationError', sprintf("%s", $e->getMessage()));
    //         }
    //     });
    // }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'email' => 'required|email',
            'password' => 'required',
            // 'domain' => [
            //     "sometimes",
            //     "regex:/^(?!\-)(?:(?:[a-zA-Z\d][a-zA-Z\d\-]{0,61})?[a-zA-Z\d]\.){1,126}(?!\d+)[a-zA-Z\d]{1,63}$/"
            // ],
        ];
    }
}
