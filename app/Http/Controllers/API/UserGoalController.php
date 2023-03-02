<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\UserGoalRequest;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\UserGoal;
use App\Models\Program;
use App\Models\User;
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
	public function readListByProgramAndUser(Organization $organization, Program $program, User $user,UserGoalService $userGoalService) {
		$limit = request()->get('pageSize', 10);
        $page = request()->get('page', 1);
        $order_direction = request()->get('order_direction', 'asc');
        $order_column = request()->get('order_column', 'name');
        $offset = ($page - 1) * $limit;
		return $userGoalService::readListByProgram($program,$user, $offset, $limit, $order_column, $order_direction);
	}
}