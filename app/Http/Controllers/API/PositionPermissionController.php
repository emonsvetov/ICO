<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Program;
use App\Models\Organization;
use App\Models\PositionPermission;

class PositionPermissionController extends Controller
{
	public function index(Organization $organization, Program $program)
	{
		return response(PositionPermission::all());
	}
}
