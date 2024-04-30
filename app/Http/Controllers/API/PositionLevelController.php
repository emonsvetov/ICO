<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PositionLevel;
use App\Models\Organization;
use App\Models\Program;
use App\Services\PositionLevelService;
use App\Http\Requests\PositionLevelRequest;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;

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
            return response()->json(['message' => 'Position level created successfully', 'data' => $positionLevel], 201);
        } catch (\Symfony\Component\HttpKernel\Exception\HttpException $e) {
            return response()->json(['message' => $e->getMessage()], $e->getStatusCode());
        }
    }

    public function index(Organization $organization, Program $program)
    {
        $positionLevels = $this->positionLevelService->getPositionLevelList($program);
        return response()->json(['data' => $positionLevels], 200);
    }

    public function show(Organization $organization, Program $program, PositionLevel $positionLevel)
    {
        $positionLevel = $this->positionLevelService->getPositionLevel($positionLevel);
        return response()->json(['data' => $positionLevel], 200);
    }

    public function update(PositionLevelRequest $positionLevelRequest, Organization $organization, Program $program, PositionLevel $positionLevel)
    {
        $data = $positionLevelRequest->validated();
        $positionLevel = $this->positionLevelService->updatePositionLevel($positionLevel, $data);
        return response()->json(['message' => 'Position level updated successfully', 'data' => $positionLevel], 200);
    }

    public function delete(PositionLevelRequest $positionLevelRequest, Organization $organization, Program $program, PositionLevel $positionLevel)
    {
        $data = $positionLevelRequest->validated();
        $positionLevel = $this->positionLevelService->deletePositionLevel($positionLevel, $data);
        return response()->json(['message' => 'Position level deleted successfully'], 200);
    }
    
}
