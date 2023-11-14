<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Services\DomainService;
use App\Models\User;
use Illuminate\Validation\Rule;

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
        return $this->request->has('code');
    }

    protected function failedAuthorization()
    {
        throw new HttpResponseException(response()->json([
            'message' => 'Code is required',
        ], 403));
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            try {
                if( !$this->domainService->isAdminAppDomain() )
                {
                    $isValidDomain = $this->domainService->isValidDomain();
                    if(is_bool($isValidDomain) && !$isValidDomain)
                    {
                        $validator->errors()->add('domain', 'Invalid host, domain or domainKey');
                    }
                }
            } catch (\Exception $e) {
                $validator->errors()->add('domain', sprintf("%s", $e->getMessage()));
            }
        });
    }

    // protected function failedValidation($validator)
    // {
    //     $errors = $validator->errors();

    //     if ($errors->has('code')) {
    //         $failedRules = $validator->failed()['code'];

    
    //             throw new HttpResponseException(response()->json([
    //                 'message' => 'Invalid Credentials.',
    //                 'errors' => $errors,
    //             ], 422));
            
    //     }

    // }

    public function messages()
    {
        return [
            'email.exists' => 'Invalid code is given',
        ];
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'email' => [
                'required',
                'email',
                Rule::exists(User::class, 'email')->where(function ($query) {
                    $query->where('twofa_verified', true)
                          ->where('token_2fa', $this->code);
                })
            ],
            'password' => 'required',
            'domainKey' => 'sometimes|string',
            'code' => [
                'required',
            ]
        ];
    }
}
