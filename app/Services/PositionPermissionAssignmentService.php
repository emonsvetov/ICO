<?php

namespace App\Services;

use App\Models\PositionLevel;
use App\Models\Organization;
use App\Models\Program;
use App\Models\PositionPermission;
use App\Models\PositionPermissionAssignment;

class PositionPermissionAssignmentService
{

	public function assignPermissionToPosition($positionId,  array $data)
	{
		foreach ($data as $permissionId) {
			$data[]= PositionPermissionAssignment::create([
				'position_level_id' => $positionId,
				'position_permission_id' => $permissionId
			]);
		}
		return $data;
	}

	public function PositionPermissionAssignment(PositionPermissionAssignment $positionLevelPermission)
    {
        $positionLevelPermission = PositionPermissionAssignment::find($positionLevelPermission);
        return $positionLevelPermission;
    }
}
