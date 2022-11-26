<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

use App\Services\DomainService;
use App\Models\User;

class ForgotPasswordRequest extends FormRequest
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

    public function __validateDomainRequest()
    {
        if($this->domainService->isValidDomainRequest())
        {
            if( $this->domainService->getIsAdminAppDomain() )
            {
                return true;
            }

            $user = User::whereEmail(request()->get('email'))->first();

            if( !$user )
            {
                throw new \InvalidArgumentException ('User not found with given email address');
            }
            
            if( $this->domainService->userHasFrontendRole( $user ) )
            {
                return true;
            }
        }
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            try {
                if( !$this->__validateDomainRequest() )
                {
                    $validator->errors()->add('validationError', 'Invalid domain or account');
                }
            } catch (\Exception $e) {
                $validator->errors()->add('validationError', $e->getMessage());
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
            'email' => 'required|email'
        ];
    }
}
