<?php

namespace App\Services\v2migrate;

use App\Http\Requests\UsersLogRequest;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\DB;
use Exception;

use App\Models\User;
use App\Models\Program;
use App\Models\UsersLog;

class MigrateUsersLogService extends MigrationService
{
    private MigrateUsersService $migrateUsersService;

    public function __construct()
    {
        parent::__construct();
    }

    public function setMigrateUsersService(MigrateUsersService $migrateUsersService)
    {
        $this->migrateUsersService = $migrateUsersService;
    }

    public function migrate(object $v2User, User $v3User, Program $v3Program)
    {
        $result = [];
        $v2UsersLogs = $this->getUsersLog($v2User->account_holder_id);

        foreach ($v2UsersLogs as $data) {
            $updateByUser=null;
            unset($data->id);
            $data->user_account_holder_id = $v3User->account_holder_id;
            $data->parent_program_id = $v3Program->id;
            $data->old_user_status_id = $data->old_user_state_id;
            $data->new_user_status_id = $data->new_user_state_id;
            if ($data->updated_by) {
                $updateBy = $this->getV2UserById($data->updated_by);
                if (!isset($updateBy->v3_user_id) || !$updateBy->v3_user_id) {
                    $updateByUser = $this->migrateUsersService->migrateSingleUser($updateBy);
                } else {
                    $updateByUser = User::find($updateBy->v3_user_id);
                    if (!$updateByUser || $updateByUser->email != $updateBy->email) {
                        $updateByUser = $this->migrateUsersService->migrateSingleUser($updateBy);
                    }
                }
                $data->updated_by = $updateByUser->id ?? null;
            }

            $usersLogRequest = new UsersLogRequest((array)$data);
            $validator = Validator::make($usersLogRequest->all(), $usersLogRequest->rules());
            $usersLogRequest->setValidator($validator);
            $newUsersLog = UsersLog::create($usersLogRequest->validated());
            if ($newUsersLog) {
                $result[] = $newUsersLog;
            }
        }
        if (count($result) !== count($v2UsersLogs)) {
            $this->printf("count(result) = " . count($result) . " count(v2UsersLogs) = " . count($v2UsersLogs) . " .\n");
        }
        return count($result) === count($v2UsersLogs);

    }

    private function getUsersLog($v2AccountHolderId): array
    {
        $this->v2db->statement("SET SQL_MODE=''");

        $sql = "
			SELECT
                *
			FROM
				users_log
			WHERE
			    user_account_holder_id = '$v2AccountHolderId'
		";

        if ($this->isPrintSql()) {
            $this->printf("SQL: %s\n", $sql);
        }

        return $this->v2db->select($sql);
    }
}
