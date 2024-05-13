<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\PositionPermissionAssignmentRequest;
use App\Http\Controllers\Controller;
use App\Models\PositionLevel;
use App\Models\Organization;
use App\Models\Program;
use App\Services\PositionLevelService;
use App\Http\Requests\PositionLevelRequest;

class PositionLevelController extends Controller
{
    protected $positionLevelService;

    public function __construct(PositionLevelService $positionLevelService)
    {
        $this->positionLevelService = $positionLevelService;
    }

    public function store(PositionLevelRequest $positionLevelRequest, Organization $organization, Program $program)
    {
        $data = $positionLevelRequest->validated();
        $data = $data + ['program_id' => $program->id];
        try {
            $positionLevel = $this->positionLevelService->createPositionLevel($data);
            return response($positionLevel);
        } catch (\Exception $e) {
            return response(['errors' => $e->getMessage(), 422]);
        }
    }

    public function index(Organization $organization, Program $program)
    {
        $filters['deleted'] = filter_var(request()->get('deleted', false), FILTER_VALIDATE_BOOLEAN);
        $filters['active'] = filter_var(request()->get('active', true), FILTER_VALIDATE_BOOLEAN);
        $positionLevels = $this->positionLevelService->getPositionLevelList($program,$filters);
        return response($positionLevels);
    }

    public function show(Organization $organization, Program $program, PositionLevel $positionLevel)
    {
        $positionLevel = $this->positionLevelService->getPositionLevel($positionLevel);
        return response($positionLevel);
    }

    public function update(PositionLevelRequest $positionLevelRequest, Organization $organization, Program $program, PositionLevel $positionLevel)
    {
        $data = $positionLevelRequest->validated();
        $positionLevel = $this->positionLevelService->updatePositionLevel($positionLevel, $data);
        return response($positionLevel);
    }

    public function delete(PositionLevelRequest $positionLevelRequest, Organization $organization, Program $program, PositionLevel $positionLevel)
    {
        $deleted = $this->positionLevelService->deletePositionLevel($positionLevel);
        return response([$deleted]);
    }

    public function assignPermissions(PositionPermissionAssignmentRequest $positionPermissionAssignmentRequest, Organization $organization, Program $program, PositionLevel $positionLevel)
	{
        $permissionIds = $positionPermissionAssignmentRequest->input('position_permission');
        $positionLevel->position_permissions()->sync($permissionIds);
		return response(['success' => true]);
	}

    public function getPermissions(Organization $organization, Program $program, PositionLevel $positionLevel)
	{
        $positionPermissions = $positionLevel->position_permissions()->get();
		return response($positionPermissions);
	}
}
