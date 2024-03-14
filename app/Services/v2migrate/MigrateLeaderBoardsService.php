<?php
namespace App\Services\v2migrate;

use App\Models\InvoiceType;
use App\Models\Program;
use App\Services\ProgramService;
use Exception;

class MigrateLeaderBoardsService extends MigrationService
{

    private ProgramService $programService;

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
            $result['info'] = "";
        } catch (\Exception $exception) {
            throw new Exception("Migrate  is failed.");
        }

        return $result;
    }

    /**
     *
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
     */
    public function migrateLeaderBoardsToProgram($v2AccountHolderID)
    {
        $v2LeaderBoards = $this->getV2LeaderBoards($v2AccountHolderID);

        foreach ($v2LeaderBoards as $v2LeaderBoard) {

        }

    }

}
