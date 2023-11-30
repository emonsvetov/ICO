<?php

namespace App\Http\Requests;

use Illuminate\Validation\Factory as ValidationFactory;
use Illuminate\Foundation\Http\FormRequest;

use App\Services\DomainService;

class MobileLoginRequest extends FormRequest
{
    protected DomainService $domainService;

    public function __construct(ValidationFactory $validationFactory, DomainService $domainService)
    {
        // $this->domainService = $domainService;
        // $request = $this->all();

        $validationFactory->extend(
            'password_validation',
            function ($attribute, $value, $parameters) {
                $request = $this->all();
                if( empty( $request['step'] ) && !$value ) {
                    return false;
                }
                return true;
            },
            'Invalid password.'
        );
        $validationFactory->extend(
            'step_validation',
            function ($attribute, $value, $parameters) {
                if( !in_array($value, ['email', 'password', 'createpassword']) ) return false;
                $request = $this->all();
                if( $value === 'password' && empty($request['password']) ) {
                    return false;
                }
                return true;
            },
            'Invalid login request or step.'
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

    public function withValidator($validator)
    {
        $request = $this->all();
        // pr($request);
        // $validator->after(function ($validator) {
        //     try {
        //         if( !$this->domainService->isAdminAppDomain() )
        //         {
        //             $isValidDomain = $this->domainService->isValidDomain();
        //             if(is_bool($isValidDomain) && !$isValidDomain)
        //             {
        //                 $validator->errors()->add('domain', 'Invalid host, domain or domainKey');
        //             }
        //         }
        //     } catch (\Exception $e) {
        //         $validator->errors()->add('domain', sprintf("%s", $e->getMessage()));
        //     }
        // });
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
            'password' => 'password_validation',
            'domainKey' => 'sometimes|string',
            'step' => 'step_validation',
        ];
    }
    public function messages()
    {
        return [
            'step'     => 'Invalid login attempt',
        ];
    }
}
