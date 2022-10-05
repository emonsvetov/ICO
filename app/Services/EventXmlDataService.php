<?php

namespace App\Services;

use App\Http\Requests\EventXmlDataRequest;
use App\Models\EventXmlData;
use App\Models\JournalEvent;
use Exception;
use Illuminate\Support\Facades\Validator;

class EventXmlDataService
{

    /**
     * @param array $data
     * @return int
     * @throws \Illuminate\Validation\ValidationException
     * @throws Exception
     */
    public function create(array $data):int
    {
        $formRequest = new EventXmlDataRequest;

        $validator = Validator::make($data, $formRequest->rules());

        if ($validator->fails()) {
            throw new Exception($validator->errors()->toJson());
        }

        $validated = $validator->validated();
        return EventXmlData::insertGetId($validated);
    }
}
