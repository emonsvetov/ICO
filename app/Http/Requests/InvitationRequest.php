<?php

namespace App\Http\Requests;

use Illuminate\Validation\Factory as ValidationFactory;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InvitationRequest extends FormRequest
{
    // public function __construct(ValidationFactory $validationFactory)
    // {
    //     $validationFactory->extend(
    //         'participantExists',
    //         function ($attribute, $value, $parameters) {
    //             // $request = $this->all();
    //             dd(request()->route()->parameters());
    //             // pr($request);
    //             return false;
    //         },
    //         'Participant exists in the program'
    //     );
    // }
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
            //'program_id' => 'required|integer',
            'first_name' => 'required|string',
			'last_name' =>  'required|string',
            'email' => [
                'required',
                'email',
                new \App\Rules\ParticipantExists
            ],
            'unit_number'=> 'sometimes|nullable|integer',
            //award level
        ];
    }
}
