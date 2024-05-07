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

		foreach ($data as $permissionId) {
			// Check if an assignment already exists for the permission ID and position ID
			$existingAssignment = PositionPermissionAssignment::where('position_permission_id', $permissionId)
				->where('position_level_id', $positionId)
				->first();

			if ($existingAssignment) {
				// Assignment exists, update it
				$existingAssignment->update([
					'position_level_id' => $positionId,
					'position_permission_id' => $permissionId
				]);

				$updatedAssignments[] = $existingAssignment;
			} else {
				// Assignment does not exist, create a new one
				$assignment = PositionPermissionAssignment::create([
					'position_level_id' => $positionId,
					'position_permission_id' => $permissionId
				]);

				$updatedAssignments[] = $assignment;
			}
		}

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
