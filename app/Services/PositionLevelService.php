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

    public function getPositionLevelList(Program $program, $filters = ['deleted' => false, 'active' => true])
    {
        $query = $program->position_levels();

        if ($filters['deleted']) {
            $query->withTrashed();
        }

        if ($filters['active']) {
            $query->where('status', 1);
        }

        return $query->with(['position_permissions'])->get();
    }

    public function getPositionLevel(PositionLevel $positionLevel)
    {
        return $positionLevel;
    }

    public function deletePositionLevel(PositionLevel $positionLevel)
    {
        return $positionLevel->delete();
    }
}
