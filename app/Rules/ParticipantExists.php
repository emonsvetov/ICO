<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class ParticipantExists implements Rule
{
    /**
     * Create a new rule instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Determine if the validation rule passes.
     *
     * @param  string  $attribute
     * @param  mixed  $value
     * @return bool
     */
    public function passes($attribute, $value)
    {
        // pr($attribute);
        // pr($value);
        $parameters = request()->route()->parameters();
        if( !empty($parameters['program']) )   {
           $program = $parameters['program'];
        }

        if( !empty($program) && $program instanceof \App\Models\Program)  {
            if( $attribute === 'email' )    {
                $user = \App\Models\User::where('email', 'like', $value)->first();
                if( $user ) return $user->isProgramParticipant($program) === false;
            }
        }

		return true;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'Participant exists in the program.';
    }
}
