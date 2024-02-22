<?php

namespace App\Services\v2migrate;

use App\Http\Requests\UsersLogRequest;
use App\Models\Program;
use App\Models\User;
use App\Models\UsersLog;
use Exception;

use App\Models\Account;
use Illuminate\Support\Facades\Validator;

class MigrateUserLogsService extends MigrationService
{
    public array $importedUserLogs = [];
    private MigrateUsersService $migrateUsersService;

    public function __construct(MigrateUsersService $migrateUsersService)
    {
        parent::__construct();
        $this->migrateUsersService = $migrateUsersService;
    }

    /**
     * @param int $v2AccountHolderID
     * @return array
     * @throws Exception
     */
    public function migrate(int $v2AccountHolderID): array
    {
        if (!$v2AccountHolderID) {
            throw new Exception("Wrong data provided. v2AccountHolderID: {$v2AccountHolderID}");
        }
        $programArgs = ['program' => $v2AccountHolderID];

        $this->printf("Starting user accounts migration\n\n",);
        $v2RootPrograms = $this->read_list_all_root_program_ids($programArgs);
        if (!$v2RootPrograms) {
            throw new Exception("No program found. v2AccountHolderID: {$v2AccountHolderID}");
        }

        $this->migrateUserLogs($v2RootPrograms);

        return $this->importedUserLogs;
    }

    /**
     * @param array $v2RootPrograms
     * @return void
     * @throws Exception
     */
    public function migrateUserLogs(array $v2RootPrograms): void
    {
        foreach ($v2RootPrograms as $v2RootProgram) {
            $this->createLogs($v2RootProgram);

            $subPrograms = $this->read_list_children_heirarchy(( int )$v2RootProgram->account_holder_id);
            foreach ($subPrograms as $subProgram) {
                $this->createLogs($subProgram);
            }
        }
    }

    /**
     * @param object $v2Program
     * @return void
     * @throws Exception
     */
    public function createLogs(object $v2Program)
    {
        $v3Program = Program::findOrFail($v2Program->v3_program_id);
        $v2users = $this->v2_read_list_by_program($v2Program->account_holder_id);

        foreach ($v2users as $v2User) {
            $v3User = User::findOrFail($v2User->v3_user_id);
            $this->migrateSingleUserLogs($v2User, $v3User, $v3Program);
        }
    }

    /**
     * @param object $v2User
     * @param User $v3User
     * @param Program $v3Program
     * @return void
     * @throws Exception
     */
    public function migrateSingleUserLogs(object $v2User, User $v3User, Program $v3Program): void
    {
        $v2UsersLogs = $this->getUsersLog($v2User->account_holder_id);

        foreach ($v2UsersLogs as $data) {
            unset($data->id);
            $data->user_account_holder_id = $v3User->account_holder_id;
            $data->parent_program_id = $v3Program->id;
            $data->old_user_status_id = $data->old_user_state_id;
            $data->new_user_status_id = $data->new_user_state_id;
            if ($data->updated_by) {
                $v2UpdateByUser = $this->getV2UserById($data->updated_by);
                if (!isset($v2UpdateByUser->v3_user_id) || !$v2UpdateByUser->v3_user_id) {
                    $v3UpdateByUser = $this->migrateUsersService->migrateOnlyUser($v2UpdateByUser, $v3Program);
                } else {
                    $v3UpdateByUser = User::find($v2UpdateByUser->v3_user_id);
                    if (!$v3UpdateByUser || ($v3UpdateByUser->email != $v2UpdateByUser->email)) {
                        $v3UpdateByUser = $this->migrateUsersService->migrateOnlyUser($v2UpdateByUser, $v3Program);
                    }
                }
                $data->updated_by = $v3UpdateByUser->id ?? null;
            }

            $usersLogRequest = new UsersLogRequest((array)$data);
            $validator = Validator::make($usersLogRequest->all(), $usersLogRequest->rules());
            $usersLogRequest->setValidator($validator);
            $this->importedUserLogs[] = UsersLog::firstOrCreate($usersLogRequest->validated(), $usersLogRequest->validated());
        }
    }
}
