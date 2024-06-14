<?php
namespace App\Services\v2migrate;
use App\Models\AwardLevel;
use App\Models\AwardLevelHasUser;
use App\Models\Program;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Exception;

class MigrateAwardLevelService extends MigrationService
{
    public $programsId;
    public $countUpdateAwardlevel = 0;
    public $countCreateAwardLevel = 0;

    public function __construct()
    {
        parent::__construct();
    }

    public function migrate($v2AccountHolderID)
    {
        $v3Program = Program::where('v2_account_holder_id', $v2AccountHolderID)->first();
        $v3AccountHolderID = $v3Program->account_holder_id ?? NULL;

        // Checking.
        if (empty($v3AccountHolderID)) {
            throw new Exception("v3 program with ID: " . $v2AccountHolderID . " not found.");
        }

        $topProgramId = $v3Program->getRoot('id')->id;
        $topLevelProgram = Program::where('id', $topProgramId)->first();
        $this->programsId = $topLevelProgram->descendantsAndSelf()->get()->pluck('id')->toArray();

        $this->syncSubProgram($v3Program);

        try {
            $result['success'] = TRUE;
            $result['info'] = "update $this->countUpdateAwardlevel items, create $this->countCreateAwardLevel items";
        } catch (\Exception $exception) {
            throw new Exception("Migrate goal plans is failed.");
        }

        return $result;
    }

    public function awardLevelsHasUsers($v2AwardLevelsId, $v3AwardLevelsId, $v3programId, $programsId)
    {
        $res = true;
        $itemsCount = 0;
        $v2AwardLevelsHasUsers = $this->v2db->select(
            sprintf("select email from award_levels_has_users as alhu
            left join users on alhu.users_id = users.account_holder_id
            where alhu.award_levels_id = %d", $v2AwardLevelsId));

        $uEmail = [];
        foreach ($v2AwardLevelsHasUsers as $val) {
            $uEmail[] = $val->email;
        }

        $programUser = DB::table('program_user')
            ->join('users as u', 'u.id', '=', 'program_user.user_id')
            ->select('u.email', 'u.id')
            ->whereIn('program_user.program_id', $programsId)
            ->whereIn('u.email', $uEmail)->get('u.email, u.id');
        $itemsCount = count($programUser->toArray());

        foreach ($programUser->toArray() as $value) {
            $user = \App\Models\User::find($value->id);
            if ($user) {
                $user->award_level = $v3AwardLevelsId;
                $user->save();
            }

            $awardLevelModel = AwardLevelHasUser::where('award_levels_id', $v3AwardLevelsId)
                ->where('users_id', $value->id)->first();
            if (!$awardLevelModel) {
                $awardLevelModel = new AwardLevelHasUser();
                $awardLevelModel->award_levels_id = $v3AwardLevelsId;
                $awardLevelModel->users_id = $value->id;
                if ($awardLevelModel->save()) {
                    $res = false;
                }
            }
        }
    }

    /**
     * Sync program hierarchy.
     *
     * @param $v3Program
     * @throws Exception
     */
    public function syncSubProgram($v3Program)
    {
        $v2AccountHolderID = $v3Program->v2_account_holder_id ?? FALSE;
        $subPrograms = $v3Program->children ?? [];

        if ($v2AccountHolderID) {
            $this->migrateAwardLevels($v2AccountHolderID, $v3Program);
        }

        if (!empty($subPrograms)) {
            foreach ($subPrograms as $subProgram) {
                $this->syncSubProgram($subProgram);
            }
        }
    }

    public function migrateAwardLevels($v2AccountHolderID, $program)
    {
        $awardLevels = $this->v2db->select(
            sprintf("select * from award_level where program_account_holder_id = %d", $v2AccountHolderID)
        );
        if (!empty($awardLevels)) {
            foreach ($awardLevels as $awardLevel) {
                $awardLevelModel = AwardLevel::where('program_account_holder_id', $v2AccountHolderID)
                    ->where('program_id', $program->id)->first();
                if ($awardLevelModel) {
                    $awardLevelModel->v2id = $awardLevel->id;
                    $awardLevelModel->save();
                    $this->countUpdateAwardlevel++;
                } else {
                    $awardLevelModel = new AwardLevel();
                    $awardLevelModel->program_account_holder_id = $v2AccountHolderID;
                    $awardLevelModel->program_id = $program->id;
                    $awardLevelModel->name = $awardLevel->name;
                    $awardLevelModel->v2id = $awardLevel->id;
                    $awardLevelModel->save();
                    $this->countCreateAwardLevel++;
                }
                $this->awardLevelsHasUsers($awardLevel->id,$awardLevelModel->id,$program->id, $this->programsId);
            }
        }
    }

}
