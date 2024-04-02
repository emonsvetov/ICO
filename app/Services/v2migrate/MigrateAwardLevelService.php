<?php
namespace App\Services\v2migrate;
use App\Models\AwardLevel;
use App\Models\AwardLevelHasUser;
use App\Models\Program;
use Illuminate\Support\Facades\DB;

class MigrateAwardLevelService extends MigrationService
{
    public function __construct()
    {
        parent::__construct();
    }

    public function migrate($v2AccountHolderID)
    {
        $res = $this->syncAwardLevel($v2AccountHolderID);
        return [
            'success' => $res['success'],
            'info' => "number of lines ". $res['itemsCount'],
        ];
    }

    public function awardLevelsHasUsers($v2AwardLevelsId, $v3AwardLevelsId, $v3programId)
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
            ->where('program_user.program_id', $v3programId)
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
        return [
            'success' => $res,
            'itemsCount' => $itemsCount,
        ];
    }

    public function syncAwardLevel($v2AccountHolderID)
    {
        $res = true;
        $itemsCount = 0;
        $v2Program = $this->v2db->select(
            sprintf("select * from programs where account_holder_id = %d", $v2AccountHolderID)
        )[0];
        $program = Program::where('name', $v2Program->name)->first();

        if (!$program) {
            return [
                'success' => $res,
                'itemsCount' => $itemsCount,
            ];
        }

        $awardLevels = $this->v2db->select(
            sprintf("select * from award_level where program_account_holder_id = %d", $v2AccountHolderID)
        );
        $itemsCount = count($awardLevels);
        foreach ($awardLevels as $awardLevel) {
            $awardLevelModel = AwardLevel::where('name', $awardLevel->name)
                ->where('program_id', $program->id)->first();
            if ($awardLevelModel) {
                $awardLevelModel->v2id = $awardLevel->id;
                $res = $awardLevelModel->save();
            } else {
                $awardLevelModel = new AwardLevel();
                $awardLevelModel->program_account_holder_id = $v2AccountHolderID;
                $awardLevelModel->program_id = $program->id;
                $awardLevelModel->name = $awardLevel->name;
                $awardLevelModel->v2id = $awardLevel->id;
                $res =  $awardLevelModel->save();
            }
            $this->awardLevelsHasUsers($awardLevel->id,$awardLevelModel->id,$program->id);
        }

        return [
            'success' => $res,
            'itemsCount' => $itemsCount,
        ];
    }
}
