<?php
namespace App\Services;
use App\Models\PositionLevel;
use App\Models\Program;

class PositionLevelService
{
    public function createPositionLevel(array $data)
    { 
        $program = new Program();
        $program_id = $program->get_top_level_program_id($data['program_id']);
        $status=1;
        if(!empty($program_id)){
            $level =$program_id + 1;
        }
        $name = 'demo name';
        try {
            // Check if the title already exists
            if (PositionLevel::where('title', $data['title'])->exists()) {
                throw new \Exception('Title already exists');
            }
            return PositionLevel::create([
                'name' => $name,
                'title' => $data['title'],
                'level' => $level,
                'program_id' => $program_id,
                'status' => $status,
            ]);
        } catch (\Exception $e) {
            return $e->getMessage();
        }
    }

    public function updatePositionLevel(PositionLevel $positionLevel, array $data)
    { 
        return $positionLevel->update($data);
    }

    public function getPositionLevelList(Program $program)
    {
        $positionLevels = PositionLevel::all();
        return $positionLevels;
    }

    public function deletePositionLevel(PositionLevel $positionLevel)
    {
        $positionLevel->status = 0; // Assuming 0 means deleted
        $positionLevel->save();
        $positionLevel->delete();
    }
}

?>
