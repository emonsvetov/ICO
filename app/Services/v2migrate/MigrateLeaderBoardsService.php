<?php
namespace App\Services\v2migrate;

use App\Models\GoalPlan;
use App\Models\InvoiceType;
use App\Models\Leaderboard;
use App\Models\Program;
use App\Services\ProgramService;
use Exception;

class MigrateLeaderBoardsService extends MigrationService
{

    private ProgramService $programService;

    public $countUpdatedLeaderBoards = 0;
    public $countCreatedLeaderBoards = 0;

    public function __construct(ProgramService $programService)
    {
        parent::__construct();
        $this->programService = $programService;
    }

    /**
     * Run migrate leader boards.
     *
     * @param $v2AccountHolderID
     * @return array
     * @throws Exception
     */
    public function migrate($v2AccountHolderID)
    {
        $result = [
            'success' => FALSE,
            'info' => '',
        ];

        $v3Program = Program::where('v2_account_holder_id', $v2AccountHolderID)->first();
        $v3AccountHolderID = $v3Program->account_holder_id ?? NULL;

        // Checking.
        if (empty($v3AccountHolderID)) {
            throw new Exception("v3 program with ID: " . $v2AccountHolderID . " not found.");
        }

        $this->getSubPrograms($v3Program);

        try {
            $result['success'] = TRUE;
            $result['info'] = "update $this->countUpdatedLeaderBoards items, create $this->countCreatedLeaderBoards items";;
        } catch (\Exception $exception) {
            throw new Exception("Migrate  is failed.");
        }

        return $result;
    }

    /**
     * Get sub program.
     */
    public function getSubPrograms($v3Program)
    {
        $programs = $this->programService->getHierarchyByProgramId($organization = FALSE, $v3Program->id)->toArray();
        $subPrograms = $programs[0]["children"] ?? FALSE;

        $v3SubProgram = Program::find($v3Program->id);
        $v2AccountHolderID = $v3SubProgram->v2_account_holder_id ?? FALSE;

        if ($v2AccountHolderID) {
            $this->migrateLeaderBoardsToProgram($v2AccountHolderID);
        }

        if (!empty($subPrograms)) {
            foreach ($subPrograms as $subProgram) {
                $this->getSubPrograms($subProgram);
            }
        }
    }

    /**
     * Migrate LeaderBoards to a program.
     *
     * @param $v2AccountHolderID
     * @throws Exception
     */
    public function migrateLeaderBoardsToProgram($v2AccountHolderID)
    {

        $v2LeaderBoards = $this->getV2LeaderBoards($v2AccountHolderID);

        $v3Program = Program::where('v2_account_holder_id', $v2AccountHolderID)->first();
        $v3AccountHolderID = $v3Program->account_holder_id ?? NULL;

        // Checking if v3 program is exists.
        if (empty($v3AccountHolderID)) {
            throw new Exception("v3 program with ID: " . $v2AccountHolderID . " not found.");
        }

        foreach ($v2LeaderBoards as $v2LeaderBoard) {

            $v3LeaderBoardData = [
                'name' => $v2LeaderBoard->name,
                'leaderboard_type_id' => $v2LeaderBoard->leaderboard_type_id, // matches with v3.
                'status_id' => $v2LeaderBoard->state_type_id, // matches with v3.
                'organization_id' => $v3Program->organization_id,
                'program_id' => $v3Program->id,
                'visible' => $v2LeaderBoard->visible,
                'one_leaderboard' => $v2LeaderBoard->one_leaderboard,
                'v2_leaderboard_id' => $v2LeaderBoard->id,
            ];

            $v3LeaderBoard = Leaderboard::where('v2_leaderboard_id', $v2LeaderBoard->id)->first();

            if (blank($v3LeaderBoard)) {
                $v3LeaderBoard = Leaderboard::create($v3LeaderBoardData);
                $this->countCreatedLeaderBoards++;
            }
            else {
                $v3LeaderBoard->update($v3LeaderBoardData);
                $this->countUpdatedLeaderBoards++;
            }
        }
    }
}
