<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\GoalPlanType;

class GoalPlanTypeController extends Controller
{
    public function index()
    {
        $goalPlanTypes = GoalPlanType::get();
        if ( $goalPlanTypes->isNotEmpty() )
        {
            return response( $goalPlanTypes );
        }
        return response( [] );
    }
}
