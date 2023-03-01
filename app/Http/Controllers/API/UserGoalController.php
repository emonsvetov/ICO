<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\UserGoalRequest;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\UserGoal;
use App\Models\Program;
use App\Services\UserGoalService;

class UserGoalController extends Controller
{
    public function createUserGoalPlans(UserGoalRequest $request, Organization $organization, Program $program, UserGoalService $userGoalService) {
		$data = $request->validated();
		try {
			$response = $userGoalService->createUserGoalPlans($organization,$program, $data);
			return response($response);
		} catch (\Exception $e )    {
			return response(['errors' => 'User goal plan creation failed','e'=>$e->getMessage()], 422);
		}
	} 
}