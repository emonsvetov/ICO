<?php
namespace App\Services\v2migrate;
use App\Models\AwardLevel;
use App\Models\Program;

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
            'success' => $res,
            'info' => "",
        ];
    }

    public function syncAwardLevel($v2AccountHolderID)
    {
        $v2Program = $this->v2db->select(
            sprintf("select * from programs where account_holder_id = %d", $v2AccountHolderID)
        )[0];
        $program = Program::where('name', $v2Program->name)->first();

        $awardLevels = $this->v2db->select(
            sprintf("select * from award_level where program_account_holder_id = %d", $v2AccountHolderID)
        );

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

        }
        return $res;
    }
}
