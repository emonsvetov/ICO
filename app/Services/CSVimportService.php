<?php

namespace App\Services;

use App\Http\Traits\UserImportTrait;
use App\Models\CsvImport;
use App\Models\CsvImportSettings;
use App\Models\CsvImportType;
use App\Models\Organization;
use Illuminate\Support\Facades\Validator;
use DB;
use DateTime;
use Illuminate\Support\Facades\Storage;

use Aws\S3\S3Client;

class CSVimportService
{
    use UserImportTrait;

    public $errors;
    public $supplied_constants;
    public $currentRowData;

    private int $line = 0;
    private array $headers = [];
    private array $saveData = [];

    /*
    1. open file
    2. read a line
    3. map the line
    4. perform validation
    5. put in bulk import list
    */

    public function importFile($file, $mapping, $supplied_constants, $setups = null)
    {
        $this->supplied_constants = $supplied_constants;

        $mapArray = json_decode($mapping, true);

        //To detect Mac line endings
        ini_set('auto_detect_line_endings', true);

        if ($file instanceof \Illuminate\Http\UploadedFile) {
            $filepath = $file->getRealPath();
        } else {
            if ($file instanceof \App\Models\CsvImport) {
                $filepath = $file['path'];
                if (config('app.env') == 'local') {
                    $filepath = '../storage/app/public/' . $filepath;
                }
            }
        }

        if (config('app.env') == 'local') {
            $handle = fopen($filepath, 'r');
        } else {
            $client = new S3Client([
                'credentials' => [
                    'key' => env('AWS_ACCESS_KEY_ID'),
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
        }

        $line = 0;
        $saveData = [];
        $this->errors = [];

        if ($handle) {
            try {
                while (($filedata = fgetcsv($handle)) !== false) {
                    if ($line == 0) {
                        #First line set the csv header to the key index e.g. $headers['first name'] = 2
                        foreach ($filedata as $key => $value) {
                            $headers[trim($value)] = $key;
                        }
                        $line++;
                        continue;
                    }
                    // return $mapArray;

                    foreach ($mapArray as $formRequest => $fieldsToMap) {
                        #instantiate the form request
                        $requestClassPath = "App\Http\Requests\\" . $formRequest;
                        $formRequestClass = new $requestClassPath;
                        $formRequestRules = $formRequestClass->rules();

                        if (method_exists($formRequestClass, 'importRules')) {

                            $fieldsWithImportRules = $formRequestClass->importRules();
                        }

                        //Initialize to avoid "Undefined array key" below (line 136, may change)
                        if ( ! isset($saveData[$formRequest][$line])) {
                            $saveData[$formRequest][$line] = [];
                        }

                        foreach ($fieldsToMap as $dbField => $csvField) {

                            $csvFieldValue = isset($headers[$csvField]) ? trim($filedata[$headers[$csvField]]) : null;

                            if ( ! empty($fieldsWithImportRules[$dbField])) {
                                # Get the rules
                                $saveData[$formRequest][$line][$dbField] = $this->getImportRule($formRequest,
                                    $fieldsWithImportRules[$dbField], $csvFieldValue, $dbField, $line);
                            } else {
                                # Each [table][database field] = csv file value
                                $saveData[$formRequest][$line][$dbField] = ($csvFieldValue !== '') ? $csvFieldValue : null;
                            }

                            $this->currentRowData[$dbField] = $saveData[$formRequest][$line][$dbField];
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

                        $formRequestRules = $this->filterRules($formRequestRules, $fieldsWithImportRules);

                        $validator = Validator::make($saveData[$formRequest][$line], $formRequestRules);

                        if ($validator->fails()) {
                            $this->errors['Line ' . $line][][$formRequest] = $validator->errors()->toArray();
                        } else {
                            $saveData[$formRequest][$line] = $validator->validated();
                        }

                    }

                    $return[] = $saveData;
                    $line++;
                }


                if ( ! empty($setups)) {
                    $notifArray = json_decode($setups, true);

                    foreach ($notifArray as $formRequest => $fields) {
                        #instantiate the form request
                        $requestClassPath = "App\Http\Requests\\" . $formRequest;
                        $formRequestClass = new $requestClassPath;

                        if (method_exists($formRequestClass, 'importSetups')) {

                            $fieldsWithImportSetups = $formRequestClass->importSetups();
                        }

                        foreach ($fields as $name => $value) {
                            if ( ! empty($fieldsWithImportSetups[$name])) {
                                # Get the rules
                                $saveData['setups'][$formRequest][$name] = $this->getImportSetup($formRequest,
                                    $fieldsWithImportSetups[$name], $value, $name);
                            } else {
                                $saveData['setups'][$formRequest][$name] = $value;
                            }
                        }

                        $validator = Validator::make($saveData['setups'][$formRequest], $formRequestClass->setups());

                        if ($validator->fails()) {
                            $this->errors['Setups'][$formRequest][] = $validator->errors()->toArray();
                        } else {
                            $saveData['setups'][$formRequest] = $validator->validated();
                        }
                    }
                }
            } catch (\Throwable $e) {
                $this->errors = 'CSVimportService with error: ' . $e->getMessage() . ' in line ' . $e->getLine();
            }
        } else {
            $this->errors = 'CSVimportService cannot read CSV file';
        }

        fclose($handle);

        ini_set('auto_detect_line_endings', false);

        if ( ! empty($this->errors)) {
            return ['errors' => $this->errors];
        }

        return $saveData;

    }


    public function filterRules($rules, $importRules)
    {
        foreach ($importRules as $key => $importRule) {
            if (str_contains($importRule, 'create:true')) {
                unset($rules[$key]);
            }
        }
        return $rules;
    }

    public function getImportRule($formRequest, $importRule, $csvValue, $dbField, $line)
    {
        if (str_contains($importRule, 'hide:true')) {
            //$formRules[$key] = 'aaa';
        } elseif (str_contains($importRule, 'mustComeFromModel:')) {
            $matchWith = $this->rule_mustComeFromModel($importRule, $csvValue);

            if ( ! empty($matchWith[trim(strtoupper($csvValue))])) {
                return (str_contains($importRule,
                    'dataType:array')) ? array($matchWith[trim(strtoupper($csvValue))]) : $matchWith[trim(strtoupper($csvValue))];
                // return $matchWith[trim(strtoupper($csvValue))];
            } else {
                $this->errors['Line ' . $line][][$formRequest][$dbField][] = "'$csvValue' did not match existing fields";
                return null;
            }
        } elseif (str_contains($importRule, 'mustExistInModel:')) {
            $existsInModel = $this->rule_mustExistInModel($importRule, $csvValue);

            if ($formRequest == 'CSVProgramRequest' && $dbField == 'name') {
                //  dd($existsInModel);
            }

            if ( ! empty($existsInModel)) {
                return $existsInModel;
            } else {
                $this->errors['Line ' . $line][][$formRequest][$dbField][] = "'$csvValue' does not exist";
            }
        } elseif (str_contains($importRule, 'date_format:')) {
            $formattedDate = $this->rule_date_format($importRule, $csvValue);

            if ( ! empty($formattedDate)) {
                return $formattedDate;
            } else {
                $this->errors['Line ' . $line][][$formRequest][$dbField][] = "'$csvValue' is not a valid date ";
            }
        } else {
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

        foreach ($rules as $ruleSet) {
            $rule = explode(':', $ruleSet);

            if ($rule[0] == 'mustExistInModel') {
                $modelName = $rule[1];
            } elseif ($rule[0] == 'use') {
                $select[] = $rule[1];
                $use = $rule[1];
            } elseif ($rule[0] == 'matchWith') {
                $select[] = $rule[1];
                $matchWith = $rule[1];
                $matchCondition[0] = $rule[1];
                $matchCondition[1] = $csvValue;
            } elseif ($rule[0] == 'filter') {
                $whereConditions[] = explode(',', $rule[1]);
            }

        }

        $modelPath = "App\Models\\" . $modelName;

        $model = new $modelPath;

        $query = $model::select($select);

        foreach ($whereConditions as $where) {
            $query = $query->where($where[0], $where[1], $this->supplied_constants[$where[2]]);
        }

        // print_r($matchCondition);
        if ( ! empty($matchCondition)) {
            $query = $query->where($matchCondition[0], $matchCondition[1]);
        }

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

        foreach ($rules as $ruleSet) {
            $rule = explode(':', $ruleSet);

            if ($rule[0] == 'mustComeFromModel') {
                $modelName = $rule[1];
            } elseif ($rule[0] == 'use') {
                $select[] = $rule[1];
                $use = $rule[1];
            } elseif ($rule[0] == 'matchWith') {
                $select[] = $rule[1];
                $matchWith = $rule[1];
            } elseif ($rule[0] == 'filter') {
                $whereConditions[] = explode(',', $rule[1]);
            } elseif ($rule[0] == 'filterConstant') {
                $filter = explode(',', $rule[1]);
                $filter[2] = $this->supplied_constants[$filter[2]];
                $whereConditions[] = $filter;
            } elseif ($rule[0] == 'filterOrNull') {
                $orWhereNullConditions[] = $rule[1];
            } elseif ($rule[0] == 'filterCsvField') {
                $filter = explode(',', $rule[1]);
                $filter[2] = $this->currentRowData[$filter[2]];
                $whereConditions[] = $filter;
            }

        }

        $modelPath = "App\Models\\" . $modelName;

        $model = new $modelPath;

        $query = $model::select($select);

        foreach ($whereConditions as $where) {
            $query = $query->where($where[0], $where[1], $where[2]);
        }

        foreach ($orWhereNullConditions as $orWhereNull) {
            $query = $query->orWhereNull($orWhereNull);
        }


        $allMatchWith = $query->get();

        //dd($allMatchWith->toArray());

        foreach ($allMatchWith as $value) {
            $matchlist[strtoupper($value[$matchWith])] = $value[$use];
        }

        return $matchlist;
    }


    public function rule_date_format($ruleSets, $csvValue)
    {
        $rules = explode('|', $ruleSets);

        foreach ($rules as $ruleSet) {
            $rule = explode(':', $ruleSet);
            if ($rule[0] == 'date_format') {
                $format = $rule[1];
            }
        }

        try {
            $date = new DateTime($csvValue);
            return $date->format($format);
        } catch (\Throwable $e) {
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
        if (str_contains($setupRule, 'mustComeFromList:')) {
            $listValues = $this->rule_mustComeFromList($setupRule, $value);
            if (in_array(strtoupper($value), $listValues)) {
                return $value;
            } else {
                $this->errors['Setups'][$formRequest][$field][] = "'$value' does not exist";
            }
        } elseif (str_contains($setupRule, 'mustComeFromModel:')) {
            $matchWith = $this->rule_mustComeFromModel($setupRule, $value);

            if ( ! empty($matchWith[trim(strtoupper($value))])) {
                return (str_contains($setupRule,
                    'dataType:array')) ? array($matchWith[trim(strtoupper($value))]) : $matchWith[trim(strtoupper($value))];
                // return $matchWith[trim(strtoupper($value))];
            } else {
                $this->errors['Setups'][$formRequest][$field][] = "'$value' does not exist";
                return null;
            }

        } else {
            return $value;
        }
    }

    public function rule_mustComeFromList($ruleSets)
    {
        $rules = explode('|', $ruleSets);

        foreach ($rules as $ruleSet) {
            $rule = explode(':', $ruleSet);

            if ($rule[0] == 'items') {
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

    public function saveSettings(array $data, Organization $organization)
    {
        $setups = json_decode($data['setups'], true);
        $fieldMapping = json_decode($data['fieldsToMap'], true);
        $type = $setups['UserRequest']['type'] ?? null;
        $csvImportTypeId = CsvImportType::getIdByName($type);

        $currentCsvImportSetting = CsvImportSettings::getByOrgAndTypeId($organization, $csvImportTypeId);

        $csvImportSetting = $currentCsvImportSetting ?: new CsvImportSettings;
        $csvImportSetting->organization_id = $organization->id;
        $csvImportSetting->csv_import_type_id = $csvImportTypeId;
        $csvImportSetting->setups = $setups;
        $csvImportSetting->field_mapping = $fieldMapping;
        $csvImportSetting->save();

        return $csvImportSetting;
    }

    public function autoImportFile(CsvImport $csvImport, $awardService)
    {
        try {
            $this->supplied_constants = collect(['organization_id' => $csvImport->organization_id]);

            if ($csvImport->rowcount === 0) {
                throw new \Exception("CSV file ({$csvImport->id}){$csvImport->name} is empty");
            }

            $csvImportSettings = CsvImportSettings::getByOrgIdAndTypeId($csvImport->organization_id, $csvImport->csv_import_type_id);
            if (!$csvImportSettings) {
                throw new \Exception("CSV Import Settings not found for ({$csvImport->id}){$csvImport->name}");
            }

            $this->field_mapping_parse($csvImport, $csvImportSettings);
            $this->setups_parse($csvImportSettings['setups']);

            $result = $this->process($csvImport, $awardService);

        } catch (\Exception $e) {
            $this->errors[] = 'autoImportFile method failed with error: ' . $e->getMessage() . ' in line ' . $e->getLine();

            $csvImport->update(['is_processed' => 0]);

            echo PHP_EOL;
            echo "errors: ";
            echo PHP_EOL;
            print_r($this->errors);
            echo PHP_EOL;
        }
    }

    private function process($csvImport, AwardService $awardService)
    {
        if (empty($this->saveData['errors'])) {
            $type = CsvImportType::find($csvImport->csv_import_type_id)->type;

            switch ($type) {
                case 'add_participants':
                    $result = $this->addUser($csvImport, $this->saveData, $this->supplied_constants);
                    break;

                case 'add_managers':
                    $result = $this->addUser($csvImport, $this->saveData, $this->supplied_constants);
                    break;

                case 'add_and_award_users':
                    $result = $this->addAndAwardUser($csvImport, $this->saveData, $this->supplied_constants);
                    break;

                case 'add_and_award_participants':
                    $result = $this->addAndAwardParticipant($csvImport, $this->saveData, $this->supplied_constants, $awardService);
                    break;

                case 'award_users':
                    $result = $this->awardUser($csvImport, $this->saveData, $this->supplied_constants);
                    break;
            }
            return $result;
        }
    }

    private function field_mapping_parse($csvImport, $csvImportSettings)
    {
        $stream = CsvImport::getAutoImportS3($csvImport);
        if (is_string($stream)) {
            $this->errors[] = $stream;
        }

        while (empty($this->errors) && (($filedata = fgetcsv($stream)) !== false)) {
            if ($this->line === 0) {
                foreach ($filedata as $key => $value) {
                    $headers[trim($value)] = $key;
                }
                $this->line++;
                continue;
            }

            foreach ($csvImportSettings['field_mapping'] as $formRequest => $fieldsToMapItem) {
                $requestClassPath = "App\Http\Requests\\" . $formRequest;
                $formRequestClass = new $requestClassPath;
                $formRequestRules = $formRequestClass->rules();

                $fieldsWithImportRules = method_exists($formRequestClass, 'importRules') ?
                    $formRequestClass->importRules() : [];
                $this->saveData[$formRequest][$this->line] = $this->saveData[$formRequest][$this->line] ?? [];

                foreach ($fieldsToMapItem as $dbField => $csvField) {
                    $csvFieldValue = isset($headers[$csvField]) ? trim($filedata[$headers[$csvField]]) : null;

                    if ($fieldsWithImportRules && ! empty($fieldsWithImportRules[$dbField])) {
                        $this->saveData[$formRequest][$this->line][$dbField] = $this->getImportRule($formRequest,
                            $fieldsWithImportRules[$dbField], $csvFieldValue, $dbField, $this->line);
                    } else {
                        $this->saveData[$formRequest][$this->line][$dbField] = ($csvFieldValue !== '') ? $csvFieldValue : null;
                    }
                    $this->currentRowData[$dbField] = $this->saveData[$formRequest][$this->line][$dbField];
                }

                $formRequestRules = $this->filterRules($formRequestRules, $fieldsWithImportRules);
                $validator = Validator::make($this->saveData[$formRequest][$this->line], $formRequestRules);

                if ($validator->fails()) {
                    $this->errors['Line ' . $this->line][][$formRequest] = $validator->errors()->toArray();
                } else {
                    $this->saveData[$formRequest][$this->line] = $validator->validated();
                }
            }
            $this->line++;
        }
        if ( ! is_string($stream)) {
            fclose($stream);
        }
    }

    private function setups_parse($setups){
        if ( ! empty($setups)) {
            foreach ($setups as $formRequest => $fields) {
                $requestClassPath = "App\Http\Requests\\" . $formRequest;
                $formRequestClass = new $requestClassPath;

                if (method_exists($formRequestClass, 'importSetups')) {
                    $fieldsWithImportSetups = $formRequestClass->importSetups();
                }

                foreach ($fields as $name => $value) {
                    if ( ! empty($fieldsWithImportSetups[$name])) {
                        $this->saveData['setups'][$formRequest][$name] = $this->getImportSetup($formRequest,
                            $fieldsWithImportSetups[$name], $value, $name);
                    } else {
                        $this->saveData['setups'][$formRequest][$name] = $value;
                    }
                }

                $validator = Validator::make($this->saveData['setups'][$formRequest], $formRequestClass->setups());

                if ($validator->fails()) {
                    $this->errors['Setups'][$formRequest][] = $validator->errors()->toArray();
                } else {
                    $this->saveData['setups'][$formRequest] = $validator->validated();
                }
            }
        }
    }
}

