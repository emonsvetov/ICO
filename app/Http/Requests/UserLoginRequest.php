<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

use App\Services\DomainService;

class UserLoginRequest extends FormRequest
{
    protected DomainService $domainService;

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
            'domainKey' => 'sometimes|string'
        ];
    }
}
