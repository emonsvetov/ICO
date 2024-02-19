<?php

namespace App\Http\Controllers\API;

use Illuminate\Support\Facades\DB;

use App\Http\Requests\PushNotificationRequest;
use App\Services\PushNotificationService;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Program;
use App\Models\User;

class PushNotificationController extends Controller
{
    public function store(PushNotificationRequest $request, PushNotificationService $pushNotificationService, Organization $organization, Program $program )
    {
        $data = $request->validated();
        try {
            $response = $pushNotificationService->firstOrCreate( $program, $data );
            return response($response);
        }   catch (\Exception $e)    {
            return response(
                [
                    // 'errors' => sprintf('Error while creating push notification. Line: %d, Error: %s', $e->getLine(), $e->getMessage()),
                    'errors' => sprintf('Error creating push notification'),
                ],
                422
            );
        }
    }
}

