<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\GoalPlanRequest;
use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Models\Program;
use App\Models\User;
//use App\Models\Role;
//use DB;


class GoalPlanController extends Controller
{
    public function store(GoalPlanRequest $request, Organization $organization, Program $program)
    {
        pr('in add func');
        //return auth()->user();
		try {
            
          //  return response([ 'user' => $user ]);
        } catch (\Exception $e )    {
           // return response(['errors' => $e->getMessage()], 422);
        }
	}
}