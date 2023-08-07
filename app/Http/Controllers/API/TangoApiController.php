<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\TeamRequest;
//use Illuminate\Support\Facades\Request;
use App\Http\Traits\TeamUploadTrait;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Organization;
use App\Models\Program;
use App\Models\TangoOrdersApi;
use DB;

class TangoApiController extends Controller
{
    public function index( Organization $organization, Program $program, Request $request )
    {
        return response(TangoOrdersApi::getActiveConfigurations());
    }

}
