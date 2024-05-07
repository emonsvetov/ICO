<?php

namespace App\Services;

use App\Models\Program;
use App\Models\PositionLevel;

class PositionLevelService
{

    public function createPositionLevel(array $data)
    {
        $program = new Program();
        $program_id = $program->get_top_level_program_id($data['program_id']);
        $status = 1;
        if (!empty($program_id)) {
            $level = $program_id + 1;
        }
        $name = 'l' . $level;
        try {
            // Check if the title already exists, including soft deleted records
            $existingPositionLevel = PositionLevel::withTrashed()->where('title', $data['title'])->first();

            if ($existingPositionLevel) {
                if ($existingPositionLevel->trashed()) {
                    // Title already exists and is inactive
                    throw new \Symfony\Component\HttpKernel\Exception\HttpException(403, 'Title already exists and inactive');
                } else {
                    // Title already exists and is active
                    throw new \Symfony\Component\HttpKernel\Exception\HttpException(403, 'Title already exists');
                }
            }

            return PositionLevel::create([
                'name' => $name,
                'title' => $data['title'],
                'level' => $level,
                'program_id' => $program_id,
                'status' => $status,
            ]);
        } catch (\Exception $e) {
            throw $e;
        }
    }


    public function updatePositionLevel(PositionLevel $positionLevel, array $data)
    {
        return $positionLevel->update($data);
    }

    public function getPositionLevelList(Program $program)
    {
        $positionLevels = PositionLevel::with('positionPermissionAssignments')
            ->where('program_id', $program->id)
            ->whereNull('deleted_at')
            ->get();
        return $positionLevels;
    }

    public function getPositionLevel(PositionLevel $positionLevel)
    {
        $positionLevel = PositionLevel::find($positionLevel);
        return $positionLevel;
    }

    public function deletePositionLevel(PositionLevel $positionLevel)
    {
        $positionLevel->status = 0; // Assuming 0 means deleted
        $positionLevel->save();
        $positionLevel->delete();
    }
}
