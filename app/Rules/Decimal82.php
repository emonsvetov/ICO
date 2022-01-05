<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;

class Decimal82 implements Rule
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
		//validation for double(8,2)
        if(preg_match("/^(\d{0,6}(\.\d{1,2})?)$/", $value)) {
			return true;
		}
		return false;
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return 'This field format is invalid.';
    }
}
