<?php

namespace App\Http\Controllers\API;

// use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

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
        $data['program_id'] = $data['program_id'] ?? $program->id;
        try {
            $response = $pushNotificationService->firstOrCreate( $data );
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
    /***
     * Send Example, only for testing
     * Note: Use App\Services\PushNotificationService service methods to send notifications
     * Add methods to PushNotificationService
     */

    public function send( Request $request, PushNotificationService $pushNotificationService, Organization $organization, Program $program )  {
        pr('Uncomment and use following code snippets to test push notification');

        //Example 1: Program Notification
        // $program = Program::find(4786);
        // $pushNotificationService->notifyUsersByProgram( $program, [
        //     'title'=>"You have a program notification",
        //     'body'=>"This is the body of program notification",
        //     'data'=>[ //to be consumed by the mobile app
        //         'param1'=>'some value',
        //         'param2'=>'some value',
        //     ]
        // ]);

        //Example 2: User Notification
        // $user = User::find(124);
        // $pushNotificationService->notifySingleUser( $user, [
        //     'title'=>"You have a notification",
        //     'body'=>"This is the body of notification",
        //     'data'=>[ //to be consumed by the mobile app
        //         'param1'=>'some value',
        //         'param2'=>'some value',
        //     ]
        // ]);

        //Example 3: Notify Multiple users by id
        // $userIds = [124, 125];
        // $pushNotificationService->notifyMultipleUsers( $userIds, [
        //     'title'=>"You have a notification",
        //     'body'=>"This is the body of notification",
        //     'data'=>[ //to be consumed by the mobile app
        //         'param1'=>'some value',
        //         'param2'=>'some value',
        //     ]
        // ]);
    }
}

