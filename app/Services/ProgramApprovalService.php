<?php

namespace App\Services;

use App\Models\Program;
use App\Models\Organization;
use App\Models\ProgramApproval;
use App\Models\ApprovalRelation;
use Illuminate\Support\Facades\DB;

class ProgramApprovalService
{


    public function index(Program $program)
    {
        // Fetch ProgramApproval records
        $programApprovals = ProgramApproval::where('program_id', $program->id)
            ->with(['approval_relations','program_approval_assignment'])
            ->get();
        return $programApprovals;
    }

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
                ///dd($approvalRequest['allow_same_step_approval']);
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

                        if ($data['allow_same_step_approval']) {
                            $this->assign_approval_relation($programApproval, $approvalRequest, $createdBy->id);
                        }
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

    public static function assign_approval_relation(ProgramApproval $programApproval, array $data, $createdById)
    {
        $approvalRelations = $data['approval_relation'] ?: [];
        $syncData = [];
        if (!empty($approvalRelations)) {
            foreach ($approvalRelations as $relation) {
                $awarderId = $relation['awarder_id'];
                $approverIds = $relation['approver_ids'] ?: [];
                if (!empty($approverIds)) {
                    foreach ($approverIds as $approverId) {
                        // Check if the entry already exists
                        $exists = DB::table('approval_relations')
                            ->where('program_approval_id', $programApproval->id)
                            ->where('awarder_position_id', $awarderId)
                            ->where('approver_position_id', $approverId)
                            ->exists();

                        if (!$exists) {
                            $syncData[] = [
                                'program_approval_id' => $programApproval->id,
                                'approver_position_id' => $approverId,
                                'awarder_position_id' => $awarderId,
                                'created_by' => $createdById,
                                'created_at' => now(),
                                'updated_at' => now()
                            ];
                        }
                    }
                }
            }
        }

        if (!empty($syncData)) {
            return DB::table('approval_relations')->insert($syncData);
        }
        return true;
    }


    public static function unassign(ProgramApproval $programApproval, array $data)
    {
        $positionLevelIds = $data['position_level_id'] ?: null;
        if ($positionLevelIds) {
            return $programApproval->position_levels()->detach($positionLevelIds);
        }
    }
}
