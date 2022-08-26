<?php
namespace App\Services;

use DB;

class CSVimportHeaderService 
{
    public $supplied_constants;
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

    public function getFieldsToMap()
    {
        $parameters = func_get_args();


        foreach ($parameters as $key => $parameter) 
        {
            if ( $key === 0 )
            {
                $result['CSVheaders'] = $this->getHeaders($parameter);
            }
            elseif ($parameter instanceof \Illuminate\Support\Collection) {
                $this->supplied_constants = $parameter;
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
                if ( method_exists($parameter, 'importSetups') )
                {
                    $result['setups'][class_basename($parameter)] = $this->getImportSetup( $parameter->importSetups() );
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
        fclose($handle);

        ini_set('auto_detect_line_endings',FALSE);

        return $headers;

    }

    public function amendFieldToMap($importRules, $formRules)
    {
        foreach ($importRules as $key => $ruleSets) 
        {
            
            if ( str_contains($ruleSets, 'hide:true') )
            {
                unset($formRules[$key]);
                
            }
            elseif ( str_contains($ruleSets, 'mustComeFromModel:') ) 
            {
                $formRules[$key] = $this->rule_mustComeFromModel($ruleSets);
            }
            elseif ( str_contains($ruleSets, 'mustExistInModel:')) 
            {
                $formRules[$key] = 'mustExist';
            }
            elseif ( isset($importRules[$key]) )
            {
                $formRules[$key] = $importRules[$key];
            }

        }

        return $formRules;
    }


    public function rule_mustComeFromModel($ruleSets)
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
            elseif ( $rule[0] == 'matchWith' ) 
            {
                $select = $rule[1];
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
        
        // exit;
        $allMatchWith = $query->get();

        foreach ($allMatchWith as $value) 
        {
            $matchlist[] = $value[$select]; 
        }
                
        return "mustMatchWith[" . implode("|", $matchlist) . "]";        
    }


    public function getImportSetup($setupRules)
    {
        foreach ($setupRules as $key => $ruleSets)
        {
            if ( str_contains($ruleSets, 'mustComeFromList:')) 
            {
                $setupRules[$key] = $this->rule_mustComeFromList($ruleSets);
            }
            elseif ( str_contains($ruleSets, 'mustComeFromModel:') ) 
            {
                $setupRules[$key] = $this->rule_mustComeFromModel($ruleSets);
            }
        }
        return $setupRules;
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
        return "mustMatchWith[" . implode("|", $items) . "]";     
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

    
}
