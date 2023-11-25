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
        $this->domainService = $domainService;
        // $request = $this->all();

        $validationFactory->extend(
            'validate_password',
            function ($attribute, $value, $parameters) {
                $request = $this->all();
                pr($request);
                // if($request['goal_plan_type_id'] == $sale_type_id) {
                //     $exceeded_event = Event::getEvent($request['exceeded_event_id']);
                //     if(!empty($exceeded_event))
                //     $exceeded_event->load('eventType');
                //     if($exceeded_event->eventType->id != EventType::getIdByTypeStandard()) {
                //         return false;
                //     }
                //  }
                 return false;
            },
            'Password is a requied field'
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
            'password' => 'validate_password|string',
            'domainKey' => 'sometimes|string',
            'step' => 'sometimes|integer',
        ];
    }
}
