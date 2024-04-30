<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use App\Services\PositionPermissionAssignmentService;
use App\Http\Requests\PositionPermissionAssignmentRequest;
use App\Models\PositionPermission;
use App\Models\PositionPermissionAssignment;
use App\Models\PositionLevel;
use App\Models\Organization;
use App\Models\Program;

class PositionPermissionAssignmentController extends Controller
{

	protected $positionPermissionAssignmentService;

	public function __construct(PositionPermissionAssignmentService $positionPermissionAssignmentService)
	{
		$this->positionPermissionAssignmentService = $positionPermissionAssignmentService;
	}

	public function getPositionPermission()
	{
		$permissions = PositionPermission::PositionPermissionList();
		return response()->json(['data' => $permissions], 200);
	}


	public function show(Organization $organization, Program $program, PositionPermissionAssignment $positionLevelPermission)
    {
        $positionLevelPermission = $this->positionPermissionAssignmentService->PositionPermissionAssignment($positionLevelPermission);
        return response()->json(['data' => $positionLevelPermission], 200);
    }

	public function assignPermissionToPosition(PositionPermissionAssignmentRequest $PositionPermissionAssignmentRequest, Organization $organization, Program $program, $positionLevelId)
	{
		$permissionIds = $PositionPermissionAssignmentRequest->input('position_permission_id');
		$data = $this->positionPermissionAssignmentService->assignPermissionToPosition($positionLevelId,$permissionIds);
		return response()->json(['message' => 'Permission assigned successfully', 'data' => $data]);
	}
}
