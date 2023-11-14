<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Services\DomainService;

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

    protected function failedValidation(Validator $validator)
    {
        $errors = $validator->errors();

        if ($errors->has('code.required')) {
            throw new HttpResponseException(response()->json([
                'message' => 'Code is required.',
                'errors' => $errors,
            ], 403));
        }


    }

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
            'domainKey' => 'sometimes|string',
            'code' => [
                'required'
            ]
        ];
    }
}
