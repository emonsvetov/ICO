<?php
namespace App\Services;

use Illuminate\Support\Facades\Validator;
use DB;
use DateTime;
use Illuminate\Support\Facades\Storage;

use Aws\S3\S3Client;

class CSVimportService 
{

    public $errors;
    public $supplied_constants;
    /*
    1. open file
    2. read a line
    3. map the line
    4. perform validation
    5. put in bulk import list
    */

    public function importFile( $file, $mapping, $supplied_constants, $setups = null )
    {
        $this->supplied_constants = $supplied_constants;

        $mapArray = json_decode($mapping, true);
        
        //To detect Mac line endings
        ini_set('auto_detect_line_endings',TRUE);

        //$filepath = $file->getRealPath();
        //$filepath = $file['path'];
        // $handle = fopen($filepath, 'r');

        $client = new S3Client([
            'credentials' => [
                'key'    =>  env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
            'region' => env('AWS_DEFAULT_REGION'),
            'version' => 'latest',
        ]);

        // Register the stream wrapper from an S3Client object
        $client->registerStreamWrapper();

        $bucket = env('AWS_BUCKET');
        $key = $file['path'];

        $handle = fopen("s3://{$bucket}/{$key}", 'r');
        
        $line = 0;
        $saveData = [];
        $this->errors = [];

        if ($handle)
        {
            try 
            {
                while ( ($filedata = fgetcsv($handle)) !== FALSE ) 
                {
                    if ($line == 0) 
                    {
                        #First line set the csv header to the key index e.g. $headers['first name'] = 2
                        foreach ($filedata as $key => $value) 
                        {
                            $headers[trim($value)] = $key;
                        }
                        $line++;
                        continue;
                    }
                    // return $mapArray;

                    foreach ($mapArray as $formRequest => $fieldsToMap) 
                    {
                        #instantiate the form request
                        $requestClassPath = "App\Http\Requests\\" . $formRequest;
                        $formRequestClass = new $requestClassPath;

                        if ( method_exists($formRequestClass, 'importRules') )
                        {

                            $fieldsWithImportRules = $formRequestClass->importRules();
                        }
                        
                        foreach ($fieldsToMap as $dbField => $csvField)
                        {                    
                            $csvFieldValue = isset($headers[$csvField]) ? trim($filedata[$headers[$csvField]]) : NULL;                        

                            if ( !empty( $fieldsWithImportRules[$dbField] ) )
                            {
                                # Get the rules
                                $saveData[$formRequest][$line][$dbField] = $this->getImportRule( $formRequest, $fieldsWithImportRules[$dbField], $csvFieldValue, $dbField, $line );
                            }
                            else 
                            {
                                # Each [table][database field] = csv file value
                                $saveData[$formRequest][$line][$dbField] =  !empty($csvFieldValue) ? $csvFieldValue : NULL;
                            }   
                        }

                        # Validate the data against the form request. 
                        // if (method_exists($formRequestClass, '_rules'))
                        // {
                        //     $validator  = Validator::make( $saveData[$formRequest][$line], $formRequestClass->_rules() );
                        // }
                        // else
                        // {
                        //     $validator  = Validator::make( $saveData[$formRequest][$line], $formRequestClass->rules() );
                        // }
                        
                        $validator  = Validator::make( $saveData[$formRequest][$line], $formRequestClass->rules() );

                        if ($validator->fails()) 
                        {
                            $this->errors['Line ' . $line][][$formRequest] = $validator->errors()->toArray();
                        }
                        else {
                            $saveData[$formRequest][$line] = $validator->validated();    
                        }
                        
                    }

                    $return[] = $saveData;
                    $line++;
                }

                
                if (!empty($setups))
                {
                    $notifArray = json_decode($setups, true);

                    foreach ($notifArray as $formRequest => $fields)
                    {
                        #instantiate the form request
                        $requestClassPath = "App\Http\Requests\\" . $formRequest;
                        $formRequestClass = new $requestClassPath;

                        if ( method_exists($formRequestClass, 'importSetups') )
                        {

                            $fieldsWithImportSetups = $formRequestClass->importSetups();
                        }

                        foreach ($fields as $name => $value)
                        {
                            if ( !empty( $fieldsWithImportSetups[$name] ) )
                            {
                                # Get the rules
                                $saveData['setups'][$formRequest][$name] = $this->getImportSetup( $formRequest, $fieldsWithImportSetups[$name], $value, $name );
                            }
                            else
                            {
                                $saveData['setups'][$formRequest][$name] = $value;
                            }
                        }

                        $validator  = Validator::make( $saveData['setups'][$formRequest], $formRequestClass->setups() );

                        if ($validator->fails()) 
                        {
                            $this->errors['Setups'][$formRequest][] = $validator->errors()->toArray();
                        }
                        else {
                            $saveData['setups'][$formRequest] = $validator->validated();    
                        }
                    }
                }
            } 
            catch (\Throwable $e) 
            {
                $this->errors = 'CSVimportService with error: ' . $e->getMessage() . ' in line ' . $e->getLine();
            }
        }
        else
        {
            $this->errors = 'CSVimportService cannot read CSV file';
        }
        
        fclose($handle);
        
        ini_set('auto_detect_line_endings',FALSE);

        if( !empty($this->errors) )
        {
            return ['errors' => $this->errors];
        }

        return $saveData;

    }

    public function getImportRule($formRequest, $importRule, $csvValue, $dbField, $line)
    {
        if ( str_contains($importRule, 'hide:true') )
        {
            //$formRules[$key] = 'aaa';
        }
        elseif ( str_contains($importRule, 'mustComeFromModel:') ) 
        {
            $matchWith = $this->rule_mustComeFromModel($importRule, $csvValue);
            
            if ( !empty($matchWith[trim(strtoupper($csvValue))]) )
            {
                return (str_contains($importRule, 'dataType:array')) ? array($matchWith[trim(strtoupper($csvValue))]) : $matchWith[trim(strtoupper($csvValue))];
                // return $matchWith[trim(strtoupper($csvValue))];
            }
            else 
            {
                $this->errors['Line ' . $line][][$formRequest][$dbField][]="'$csvValue' did not match existing fields";
                return NULL;    
            }
            
        }
        elseif ( str_contains($importRule, 'mustExistInModel:') )
        {
            $existsInModel = $this->rule_mustExistInModel($importRule, $csvValue);

            if ($formRequest=='CSVProgramRequest' && $dbField=='name')
            {
              //  dd($existsInModel);
            }

            if ( !empty($existsInModel) )
            {
                return $existsInModel; 
            }
            else 
            {
                $this->errors['Line ' . $line][][$formRequest][$dbField][]="'$csvValue' does not exist";
            }
        }
        elseif ( str_contains($importRule, 'date_format:') )
        {
            $formattedDate = $this->rule_date_format($importRule, $csvValue);
            
            if ( !empty($formattedDate) )
            {
                return $formattedDate; 
            }
            else 
            {
                $this->errors['Line ' . $line][][$formRequest][$dbField][]="'$csvValue' is not a valid date ";
            }
        }
        else
        {
            return $csvValue;
        }
    }
/*
    public function getImportRules($importRules)
    {
        $formRules = [];
        foreach ($importRules as $key => $ruleSets) 
        {
            
            if ( str_contains($ruleSets, 'hide:true') )
            {
                $formRules[$key] = 'aaa';
                
            }
            elseif ( str_contains($ruleSets, 'matchWith:') ) 
            {
                $formRules[$key] = $this->rule_matchWith($ruleSets);
            }

        }

        return $formRules;
    }
*/
    public function rule_mustExistInModel($ruleSets, $csvValue)
    {
        //'mustExistInModel:Program|matchWith:external_id|use:external_id|filter:organization_id,=,$this->supplied_constants["organization_id"]'
        $rules = explode('|', $ruleSets);

        $whereConditions = [];
        $matchlist = [];

        foreach ( $rules as $ruleSet) 
        {
            $rule = explode(':', $ruleSet);

            if ( $rule[0] == 'mustExistInModel' )
            {
                $modelName = $rule[1];
            }
            elseif ( $rule[0] == 'use' ) 
            {
                $select[] = $rule[1];
                $use = $rule[1];
            }
            elseif ( $rule[0] == 'matchWith' ) 
            {
                $select[] = $rule[1];
                $matchWith = $rule[1];
                $matchCondition[0] = $rule[1];
                $matchCondition[1] = $csvValue;
            }
            elseif ( $rule[0] == 'filter' )
            {
                $whereConditions[] = explode(',', $rule[1]);
            }
            
        }

        $modelPath = "App\Models\\" . $modelName;

        $model = new $modelPath;

        $query = $model::select($select);

        foreach ($whereConditions as $where) 
        {
            $query = $query->where($where[0], $where[1], $this->supplied_constants[$where[2]]);
        }

        $query = $query->where($matchCondition[0], $matchCondition[1]);
    
        $allMatchWith = $query->first();

        return is_null($allMatchWith) ? null : $allMatchWith[$use];
       
    }


    public function rule_mustComeFromModel($ruleSets, $csvValue)
    {
        //'mustComeFromModel:Status|matchWith:status|use:id|filter:context=Users'
        $rules = explode('|', $ruleSets);

        $whereConditions = [];
        $matchlist = [];
        $orWhereNullConditions = [];

        foreach ( $rules as $ruleSet) 
        {
            $rule = explode(':', $ruleSet);

            if ( $rule[0] == 'mustComeFromModel' )
            {
                $modelName = $rule[1];
            }
            elseif ( $rule[0] == 'use' ) 
            {
                $select[] = $rule[1];
                $use = $rule[1];
            }
            elseif ( $rule[0] == 'matchWith' ) 
            {
                $select[] = $rule[1];
                $matchWith = $rule[1];
            }
            elseif ( $rule[0] == 'filter' )
            {
                $whereConditions[] = explode(',', $rule[1]);
            }
            elseif ( $rule[0] == 'filterConstant' )
            {
                $filter = explode(',', $rule[1]);
                $filter[2] = $this->supplied_constants[$filter[2]];
                $whereConditions[] = $filter;
            }
            elseif ( $rule[0] == 'filterOrNull' )
            {
                $orWhereNullConditions[] = $rule[1];
            }
            
        }

        $modelPath = "App\Models\\" . $modelName;

        $model = new $modelPath;

        $query = $model::select($select);

        foreach ($whereConditions as $where) 
        {            
            $query = $query->where($where[0],$where[1],$where[2]);
        }

        foreach ($orWhereNullConditions as $orWhereNull)
        {
            $query = $query->orWhereNull($orWhereNull);
        }

        
        $allMatchWith = $query->get();

        //dd($allMatchWith->toArray());

        foreach ($allMatchWith as $value) 
        {
            $matchlist[strtoupper($value[$matchWith])] = $value[$use];
        }
        
        return $matchlist;
    }


    public function rule_date_format($ruleSets, $csvValue)
    {
        $rules = explode('|', $ruleSets);

        foreach ($rules as $ruleSet)
        {
            $rule = explode(':', $ruleSet);
            if ($rule[0] == 'date_format')
            {
                $format = $rule[1];
            }
        }
        
        try
        {
            $date = new DateTime($csvValue);
            return $date->format($format);
        }
        catch (\Throwable $e) 
        {
            return false;
        }
        
        // $day = $date->format('d');
        // $month = $date->format('m');
        // $year = $date->format('Y');

        // if (checkdate($month, $day, $year))
        // {
        //     return $date->format($format);
        // }
        
        // return false;
    }


    public function getImportSetup($formRequest, $setupRule, $value, $field)
    {
        if ( str_contains($setupRule, 'mustComeFromList:')) 
        {
            $listValues = $this->rule_mustComeFromList($setupRule, $value);
            if (in_array(strtoupper($value), $listValues))
            {
                return $value;
            }
            else
            {
                $this->errors['Setups'][$formRequest][$field][] = "'$value' does not exist";
            }
        }
        elseif ( str_contains($setupRule, 'mustComeFromModel:') ) 
        {
            $matchWith = $this->rule_mustComeFromModel($setupRule, $value);
            
            if ( !empty($matchWith[trim(strtoupper($value))]) )
            {
                return (str_contains($setupRule, 'dataType:array')) ? array($matchWith[trim(strtoupper($value))]) : $matchWith[trim(strtoupper($value))];
                // return $matchWith[trim(strtoupper($value))];
            }
            else 
            {
                $this->errors['Setups'][$formRequest][$field][] = "'$value' does not exist";
                return NULL;    
            }
            
        }
        else
        {
            return $value;
        }
    }

    public function rule_mustComeFromList($ruleSets)
    {
        $rules = explode('|', $ruleSets);

        foreach ( $rules as $ruleSet) 
        {
            $rule = explode(':', $ruleSet);

            if ( $rule[0] == 'items' )
            {
                $items = explode(',', $rule[1]);
            }
        }
        
        return array_map('strtoupper', $items);
    }

    # DELETE BELOW
    
    /**
     * 
     * getFieldsToMap() is used to get the headers of a CSV file along with the fields to which those headers should match with
     * 
     * @param file The uploaded file from the request
     * @param class The form request class used to validate the data e.g. new \App\Http\Requests\UserRequest
     * @param class Addiotnal form request class. Add as many as you want e.g. new \App\Http\Requests\OrganizationRequest
     * @param class Addiotnal form request class. Add as many as you want e.g. new \App\Http\Requests\ProgramRequest
     * 
     * @return array [CSVheaders] contains all the headers of the first line of the CSV file. [fieldsToMap] contains all fields that can be mapped
     */
/*
    public function getFieldsToMap()
    {
        $parameters = func_get_args();


        foreach ($parameters as $key => $parameter) 
        {
            if ( $key === 0 )
            {
                $result['CSVheaders'] = $this->getHeaders($parameter);
            }
            else 
            {
                if ( method_exists($parameter, 'importRules') )
                {
                    $result['fieldsToMap'][class_basename($parameter)] = $this->amendFieldToMap( $parameter->importRules(), $parameter->rules());
                }
                else {
                    $result['fieldsToMap'][class_basename($parameter)] = $parameter->rules();
                }
            }
        }

        return $result;
    }
    
    public function getHeaders( $requestFile ) 
    {
        
        //To detect Mac line endings
        ini_set('auto_detect_line_endings',TRUE);

        $filepath = $requestFile->getRealPath();

        $handle = fopen($filepath, 'r');        
        $headers = fgetcsv($handle);

        ini_set('auto_detect_line_endings',FALSE);

        return $headers;

    }

    public function amendFieldToMap($importRules, $formRules)
    {
        foreach ($importRules as $key => $ruleSets) {
            
            if ( str_contains($ruleSets, 'hide:true') )
            {
                unset($formRules[$key]);
                
            }
            elseif ( str_contains($ruleSets, 'matchWith:') ) 
            {
                $formRules[$key] = $this->rule_matchWith($ruleSets);
            }

        }

        return $formRules;
    }


    


    public function uploadContent(Request $request)
    {
        $file = $request->file('uploaded_file');
        if ($file) {
            
            $filename = $file->getClientOriginalName();
            $extension = $file->getClientOriginalExtension(); //Get extension of uploaded file
            $tempPath = $file->getRealPath();
            $fileSize = $file->getSize(); //Get size of uploaded file in bytes

            //Check for file extension and size
            $this->checkUploadedFileProperties($extension, $fileSize);
            //Where uploaded file will be stored on the server 
            $location = 'uploads'; //Created an "uploads" folder for that
            // Upload file
            $file->move($location, $filename);
            // In case the uploaded file path is to be stored in the database 
            $filepath = public_path($location . "/" . $filename);
            // Reading file
            $file = fopen($filepath, "r");
            $importData_arr = array(); // Read through the file and store the contents as an array
            $i = 0;

            //Read the contents of the uploaded file 
            while (($filedata = fgetcsv($file, 1000, ",")) !== FALSE) 
            {
                $num = count($filedata);
                // Skip first row (Remove below comment if you want to skip the first row)
                if ($i == 0) {
                    $i++;
                    continue;
                }

                for ($c = 0; $c < $num; $c++) {
                    $importData_arr[$i][] = $filedata[$c];
                }
                $i++;
            }

            fclose($file); //Close after reading
            $j = 0;

            foreach ($importData_arr as $importData) 
            {
                $name = $importData[1]; //Get user names
                $email = $importData[3]; //Get the user emails
                $j++;

                try 
                {
                    DB::beginTransaction();
                    
                    Player::create([
                        'name' => $importData[1],
                        'club' => $importData[2],
                        'email' => $importData[3],
                        'position' => $importData[4],
                        'age' => $importData[5],
                        'salary' => $importData[6]
                    ]);

                    //Send Email
                    $this->sendEmail($email, $name);

                    DB::commit();
                } catch (\Exception $e) {
                    //throw $th;
                    DB::rollBack();
                }
            }

            return response()->json([
                'message' => "$j records successfully uploaded"
            ]);

        } else {
            //no file was uploaded
            throw new \Exception('No file was uploaded', Response::HTTP_BAD_REQUEST);
        }
    }


    public function checkUploadedFileProperties($extension, $fileSize)
    {
        $valid_extension = array("csv", "xlsx"); //Only want csv and excel files
        $maxFileSize = 2097152; // Uploaded file size limit is 2mb

        if (in_array(strtolower($extension), $valid_extension)) 
        {
            if ($fileSize <= $maxFileSize) {
            } else {
                throw new \Exception('No file was uploaded', Response::HTTP_REQUEST_ENTITY_TOO_LARGE); //413 error
            }
        } 
        else 
        {
            throw new \Exception('Invalid file extension', Response::HTTP_UNSUPPORTED_MEDIA_TYPE); //415 error
        }
    }


    public function sendEmail($email, $name)
    {
        $data = array(
            'email' => $email,
            'name' => $name,
            'subject' => 'Welcome Message',
        );
        Mail::send('welcomeEmail', $data, function ($message) use ($data) {
            $message->from('welcome@myapp.com');
            $message->to($data['email']);
            $message->subject($data['subject']);
        });
    }

    */
}
