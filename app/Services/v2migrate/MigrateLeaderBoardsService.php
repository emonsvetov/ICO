<?php
namespace App\Services\v2migrate;

use App\Models\Event;
use App\Models\GoalPlan;
use App\Models\InvoiceType;
use App\Models\JournalEvent;
use App\Models\Leaderboard;
use App\Models\LeaderboardJournalEvent;
use App\Models\Program;
use App\Services\ProgramService;
use Exception;

class MigrateLeaderBoardsService extends MigrationService
{

    public $countUpdatedLeaderBoards = 0;
    public $countCreatedLeaderBoards = 0;

    public function __construct()
    {
        parent::__construct();
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
        $v2AccountHolderID = $v3Program->v2_account_holder_id ?? FALSE;
        $subPrograms = $v3Program->children ?? [];

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

            $this->migrateLeaderBoardEvents($v3LeaderBoard);
            $this->migrateLeaderboardJournalEvents($v3LeaderBoard);
            $this->migrateLeaderboardGoalPlans($v3LeaderBoard);
        }
    }

    /**
     * Migrate leader board events.
     *
     * @param $v3LeaderBoard
     */
    public function migrateLeaderBoardEvents($v3LeaderBoard)
    {
        $v2LeaderBoardEvents = $this->getV2LeaderBoardEvents($v3LeaderBoard->v2_leaderboard_id);
        if (!empty($v2LeaderBoardEvents)) {
            $v2EventIDs = [];
            foreach ($v2LeaderBoardEvents as $v2LeaderBoardEvent) {
                $v2EventIDs[] = $v2LeaderBoardEvent->event_template_id;
            }

            $v3Events = Event::whereIn('v2_event_id', $v2EventIDs)->get();
            $v3LeaderBoard->events()->sync($v3Events);
        }
    }

    /**
     * Migrate leader board journal events.
     *
     * @param $v3LeaderBoard
     */
    public function migrateLeaderboardJournalEvents($v3LeaderBoard)
    {
        $v2LeaderBoardJournalEvents = $this->getV2LeaderBoardJournalEvents($v3LeaderBoard->v2_leaderboard_id);
        if (!empty($v2LeaderBoardJournalEvents)) {
            $v2JournalEventIDs = [];
            foreach ($v2LeaderBoardJournalEvents as $v2LeaderBoardJournalEvent) {
                $v2JournalEventIDs[] = $v2LeaderBoardJournalEvent->journal_event_id;
            }

            $v3JournalEvents = JournalEvent::whereIn('v2_journal_event_id', $v2JournalEventIDs)->get();
            if (!empty($v3JournalEvents)) {
                foreach ($v3JournalEvents as $v3JournalEvent) {
                    LeaderboardJournalEvent::create([
                        'leaderboard_id' => $v3LeaderBoard->id,
                        'journal_event_id' => $v3JournalEvent->id,
                    ]);
                }
            }
        }
    }

    /**
     * Migrate leader board goal plans.
     *
     * @param $v3LeaderBoard
     */
    public function migrateLeaderboardGoalPlans($v3LeaderBoard)
    {
        $v2LeaderBoardGoalPlans = $this->getV2LeaderBoardGoalPlans($v3LeaderBoard->v2_leaderboard_id);
        if (!empty($v2LeaderBoardGoalPlans)) {
            $v2GoalPlanIDs = [];
            foreach ($v2LeaderBoardGoalPlans as $v2LeaderBoardGoalPlan) {
                $v2GoalPlanIDs[] = $v2LeaderBoardGoalPlan->goal_plan_id;
            }

            $v3GoalPlans = GoalPlan::whereIn('v2_goal_plan_id', $v2GoalPlanIDs)->get();
            $v3LeaderBoard->goalPlans()->sync($v3GoalPlans);
        }
    }

}
