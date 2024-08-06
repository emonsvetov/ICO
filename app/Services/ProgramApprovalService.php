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
            ->with([
                'program_approval_assignment.position_level',
                'approval_relations.awarder_position_level',
                'approval_relations.approver_position_level',
            ])
            ->get();
        return $programApprovals;
    }

    public function createProgramApprovalStep(array $data)
    {
        $createdBy = auth()->user();
        $program = new Program();
        $status = 1;
        foreach ($data['program_id'] as $program_id) {

            $allow_same_step_approval =  (isset($data['allow_same_step_approval']) && $data['allow_same_step_approval'] == 1) ? 1 : 0;
            Program::where('id', $program_id)
                ->update(['allow_same_step_approval' => $allow_same_step_approval]);

            $parent_id = $program->get_top_level_program_id($program_id);

            foreach ($data['approval_request'] as $approvalRequest) {
                $step = $approvalRequest['step'];

                if ($step > 0) {
                    try {
                        // Check for existing ProgramApproval with the same program_id and step
                        $existingApprovals = ProgramApproval::where('program_id', $program_id)->where('step', $step)
                            ->get();

                        // Delete existing ProgramApprovals if they exist
                        foreach ($existingApprovals as $existingApproval) {
                            $existingApproval->program_approval_assignment()->delete();
                            $existingApproval->approval_relations()->delete(); // Delete related approval relations
                            $existingApproval->delete(); // Delete the ProgramApproval
                        }

                        // Create new ProgramApproval for each program_id and step
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

                        if ($exists) {
                            throw new \Exception("Duplicate entry found for programapproval relations");
                        } else {
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
        return true; // Return true if no new data to insert
    }

    public static function unassign(ProgramApproval $programApproval, array $data)
    {
        $positionLevelIds = $data['position_level_id'] ?: null;
        if ($positionLevelIds) {
            return $programApproval->position_levels()->detach($positionLevelIds);
        }
    }


    public function getAwardApprovalStepData($programId, $userId)
    {
        try {
            $result = [];

            // Fetch parent program ID
            $parentProgramId = Program::getTopLevelProgramId($programId); // Assuming getTopLevelProgramId is a static method in Program model

            // Fetch approval flow program ID
            $approvalFlowProgramID = ProgramApproval::getApprovalsProgramId($programId); // Assuming getApprovalsProgramId is a static method in ProgramApproval model

            if (empty($approvalFlowProgramID)) {
                return $result;
            }

            // Build the main query using Laravel Query Builder
            $queryResult = ProgramApproval::select('pa.*', 'p.allow_same_step_approval')
                ->join('programs as p', 'p.account_holder_id', '=', 'pa.program_id')
                ->join('program_approval_assignment as paa', 'paa.program_approval_id', '=', 'pa.id')
                ->join('position_levels as pl', 'pl.id', '=', 'paa.position_level_id')
                ->join('position_assignment as a', 'a.position_level_id', '=', 'pl.id')
                ->where('pa.status', 1)
                ->where('pa.program_id', $approvalFlowProgramID)
                ->where('a.user_id', $userId)
                ->where('pa.program_parent_id', $parentProgramId)
                ->whereIn('a.program_id', function ($query) use ($programId) {
                    $query->select('ancestor')
                        ->from('program_paths')
                        ->where('descendant', $programId);
                })
                ->orderBy('pa.step')
                ->first();

            // If queryResult is not empty, check awarder_position_id and return if conditions met
            if (!empty($queryResult)) {
                if ($queryResult->allow_same_step_approval == 1) {
                    $awarderPositionId = ApprovalRelation::where('program_approval_id', $queryResult->id)
                        ->where('awarder_position_id', $queryResult->position_level_id)
                        ->value('awarder_position_id');
                    if ($awarderPositionId > 0) {
                        return $queryResult;
                    }
                }
            }

            // Build second query based on conditions
            $secondQueryResult = ProgramApproval::where('status', 1)
                ->where('program_id', $approvalFlowProgramID)
                ->where('program_parent_id', $parentProgramId);

            if (!empty($queryResult)) {
                $secondQueryResult->where('step', '>', $queryResult->step);
            } else {
                $secondQueryResult->where('step', 1);
            }

            $result = $secondQueryResult->orderBy('step')
                ->first();

            return $result;
        } catch (\Exception $ex) {
            throw new \RuntimeException($ex->getMessage(), 500);
        }
    }
}
