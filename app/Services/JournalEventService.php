<?php

namespace App\Services;

use App\Http\Requests\JournalEventRequest;
use App\Models\JournalEvent;
use Exception;
use Illuminate\Support\Facades\Validator;

class JournalEventService
{

    /**
     * @param array $data
     * @return int
     * @throws \Illuminate\Validation\ValidationException
     * @throws Exception
     */
    public function create(array $data):int
    {
        $formRequest = new JournalEventRequest;

        $validator = Validator::make($data, $formRequest->rules());

        if ($validator->fails()) {
            throw new Exception($validator->errors()->toJson());
        }

        $validated = $validator->validated();
        if( empty($validated['created_at']) )
        {
            $validated['created_at'] = now();
        }
        return JournalEvent::insertGetId($validated);
    }
}
