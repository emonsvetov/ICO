<?php

namespace App\Rules;

use Illuminate\Contracts\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use App\Http\Traits\CsvParser;

class CsvContent implements Rule
{
    use CsvParser;

    protected $errors = ['Value is not valid'];
    protected $csvData = [];
    protected $rules = [];
    /**
     * Create a new rule instance.
     *
     * @return void
     */

    public function __construct( $rules )
    {
        $this->setRules( $rules );
    }

    private function setRules($rules)
    {
        $this->rules = $rules;
        // $this->headingKeys = array_keys($rules);
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
        if( !$this->rules ) {
            $this->errors = 'Csv validation rules not found';
            return false;
        }

        if( request()->has($attribute) ) {
            // $value->get(); //get contents of file! Or use request()->file($attribute)->get()
            $csvData = $this->CsvToArray( $value->get() );
            if (empty($csvData)) {
                $this->errors = 'Empty csv data';
                return false;
            }
            $newCsvData = [];
            $ruleKeys = array_keys($this->rules);
            foreach ($csvData as $rowIndex => $csvValues) {
                foreach ($ruleKeys as $ruleKeyIndex) {
                    $newCsvData[$rowIndex][$ruleKeyIndex] = $csvValues[$ruleKeyIndex];
                }
            }
            $this->csvData = $newCsvData;
            $errors = [];
            try{
                $hasError = false;
                foreach ($newCsvData as $rowIndex => $csvValues) {
                    $errors[$rowIndex] = null; //need to keep every index intact
                    $validator = Validator::make($csvValues, $this->rules);
                    // if (!empty($this->headingRow)) {
                    //     $validator->setAttributeNames($this->headingRow);
                    // }
                    if (!$validator->errors()->isEmpty()) {
                        $errors[$rowIndex] = $validator->messages()->toArray();
                        $hasError = true;
                    }
                }
                if( $errors && $hasError  )   {  //check if it is a fail and hence set errors
                    $this->errors = $errors;
                    return false;
                }
            }
            catch (\Exception $e)   {
                echo $e->getMessage();
            }
            return true;
        }
    }

    /**
     * Get the validation error message.
     *
     * @return string
     */
    public function message()
    {
        return  json_encode(['errors'=>$this->errors, 'rows'=>$this->csvData]);
    }
}
