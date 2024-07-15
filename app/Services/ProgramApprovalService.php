<?php

namespace App\Services;

use App\Models\Program;
use App\Models\Organization;
use App\Models\ProgramApproval;

class ProgramApprovalService
{

    /* public function createProgramApprovalStep(array $data)
    {
        $createdBy = auth()->user();
        $program = new Program();
        $parent_id = $program->get_top_level_program_id($data['program_id']);
        $status = 1;
        try {
            return ProgramApproval::create([
                'step' => $data['step'],
                'program_id' => $data['program_id'],
                'program_parent_id' => $parent_id,
                'status' => $status,
                'created_by' => $createdBy->id,
            ]);
        } catch (\Exception $e) {
            throw $e;
        }
    }*/

    public function createProgramApprovalStep(array $data)
    {
        $createdBy = auth()->user();
        $program = new Program();
        $status = 1;
        foreach ($data['program_id'] as $program_id) {
            $parent_id = $program->get_top_level_program_id($program_id);
            foreach ($data['approval_request'] as $approvalRequest) {
                $step = $approvalRequest['step'];
                if ($step > 0) {
                    try {
                        // Create ProgramApproval for each program_id and step
                        $programApproval = ProgramApproval::create([
                            'step' => $step,
                            'program_id' => $program_id,
                            'program_parent_id' => $parent_id,
                            'status' => $status,
                            'created_by' => $createdBy->id,
                        ]);

                        // Assign position levels to the created ProgramApproval
                        $this->assign($programApproval, $approvalRequest);
                    } catch (\Exception $e) {
                        throw $e;
                    }
                }
            }
        }
    }

    public function updateProgramApprovalStep(ProgramApproval $programApproval, array $data)
    {
        $programApproval->update($data);
        $programApproval->refresh();
        return $programApproval;
    }

    public function getProgramApprovalStep(ProgramApproval $programApproval)
    {
        return $programApproval;
    }

    public function deleteProgramApprovalStep(ProgramApproval $programApproval)
    {
        return $programApproval->delete();
    }

    public static function assign(ProgramApproval $programApproval, array $data)
    {
        $positionLevelIds = $data['position_level_id'] ?: null;
        if ($positionLevelIds) {
            return $programApproval->position_levels()->sync($positionLevelIds);
        }
    }

    public static function unassign(ProgramApproval $programApproval, array $data)
    {
        $positionLevelIds = $data['position_level_id'] ?: null;
        if ($positionLevelIds) {
            return $programApproval->position_levels()->detach($positionLevelIds);
        }
    }
}
