<?php

namespace App\Services;

use App\Models\Program;
use App\Models\Organization;
use App\Models\PositionLevel;
use App\Models\PositionPermission;
use App\Models\PositionPermissionAssignment;

class PositionPermissionAssignmentService
{

	public function assignPermissionToPosition($positionId, array $data)
	{
		$updatedAssignments = [];
		// Fetch all existing assignments
		$existingAssignments = PositionPermissionAssignment::where('position_level_id', $positionId)->get();
		$existingAssignmentIds = $existingAssignments->pluck('position_permission_id')->toArray();
		foreach ($data as $permissionId) {
			// Check if an existing assignment
			if (in_array($permissionId, $existingAssignmentIds)) {
				// If it matches
				$key = array_search($permissionId, $existingAssignmentIds);
				unset($existingAssignmentIds[$key]);
			} else {
				//create a new assignment
				$assignment = PositionPermissionAssignment::create([
					'position_level_id' => $positionId,
					'position_permission_id' => $permissionId
				]);
				$updatedAssignments[] = $assignment;
			}
		}

		// Delete any existing assignments that are not in the new data
		PositionPermissionAssignment::where('position_level_id', $positionId)
			->whereIn('position_permission_id', $existingAssignmentIds)
			->delete();
		return $updatedAssignments;
	}

	public function PositionPermissionAssignment(PositionPermissionAssignment $positionLevelPermission)
	{
		$positionLevelPermission = PositionPermissionAssignment::find($positionLevelPermission);
		return $positionLevelPermission;
	}

	public function getAssignedPermissions($positionId)
	{
		$positionLevelPermissions = PositionPermissionAssignment::where('position_level_id', $positionId)
			->with('positionPermission')
			->get();

		return $positionLevelPermissions;
	}
}
