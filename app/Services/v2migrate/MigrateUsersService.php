<?php

namespace App\Services\v2migrate;

use Illuminate\Support\Facades\Validator;
use App\Http\Requests\UserRequest;
use App\Models\Program;
use Exception;

use App\Models\User;
use App\Models\UserV2User;
use App\Models\Role;

class MigrateUsersService extends MigrationService
{
    public array $importedUsers = [];

    public function __construct()
    {
        parent::__construct();
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

        $this->printf("Starting users migration\n\n",);
        $v2RootPrograms = $this->read_list_all_root_program_ids($programArgs);
        if (!$v2RootPrograms) {
            throw new Exception("No program found. v2AccountHolderID: {$v2AccountHolderID}");
        }

        $this->migrateUsers($v2RootPrograms);

        return [
            'success' => TRUE,
            'info' => "migrated " . count($this->importedUsers) . " items",
        ];

    }

    /**
     * @param array $v2RootPrograms
     * @return void
     * @throws Exception
     */
    public function migrateUsers(array $v2RootPrograms): void
    {
        foreach ($v2RootPrograms as $v2RootProgram) {
            $v2Program = $this->get_program_info($v2RootProgram->account_holder_id);
            if (!$v2Program) {
                throw new Exception("Program info not found. v2RootProgram: {$v2RootProgram->account_holder_id}");
            }

            $this->syncOrCreateUsers($v2Program);

            $subPrograms = $this->read_list_children_heirarchy(( int )$v2Program->account_holder_id);
            foreach ($subPrograms as $subProgram) {
                $this->syncOrCreateUsers($subProgram);
            }
        }
    }

    /**
     * @throws Exception
     */
    public function syncOrCreateUsers($v2Program)
    {
        $v3Program = Program::findOrFail($v2Program->v3_program_id);
        $v2users = $this->v2_read_list_by_program($v2Program->account_holder_id);

        foreach ($v2users as $v2User) {
            $this->importedUsers[$v2User->account_holder_id] = $this->migrateSingleUser($v2User, $v2Program, $v3Program);
        }
    }

    /**
     * @param object $v2User
     * @param object $v2Program
     * @param Program $v3Program
     * @return User
     * @throws Exception
     */
    public function migrateSingleUser(object $v2User, object $v2Program, Program $v3Program): User
    {
        $this->printf("Starting migration of v2user: {$v2User->account_holder_id} with email: {$v2User->email}\n");

        $v3User = User::getByEmail($v2User->email);
        if ($v3User) {
            $v3User->update(['organization_id' => $v3Program->organization_id]);
        } else {
            $v3User = $this->createUser($v2User, $v3Program);
        }

        $this->syncUserAssoc($v2User, $v3User);
        if ($v2User->v3_user_id != $v3User->id) {
            $this->v2db->statement(sprintf("
                    UPDATE `users`
                    SET `v3_user_id`=%d
                    WHERE account_holder_id=%d;
            ", $v3User->id, $v2User->account_holder_id));
        }

        $this->migrateRoles($v2User, $v3User, $v2Program);

        return $v3User;
    }

    /**
     * @param object $v2User
     * @param User $v3User
     * @return void
     */
    public function syncUserAssoc(object $v2User, User $v3User): void
    {
        $userV2user = $v3User->v2_users()->where('v2_user_account_holder_id', $v2User->account_holder_id)->first();
        if ($userV2user) {
            if ($userV2user->user_id != $v3User->id) {
                $userV2user->user_id = $v3User->id;
                $userV2user->save();
            }
        } else {
            $newAssoc = new UserV2User([
                'user_id' => $v3User->id,
                'v2_user_account_holder_id' => $v2User->account_holder_id,
            ]);
            $v3User->v2_users()->save($newAssoc);
        }
    }


    public function createUser($v2User, Program $v3Program)
    {
        if ((int)$v2User->birth_month && (int)$v2User->birth_day) {
            $dob = "1970-" . ((int)$v2User->birth_month < 10 ? "0" . (int)$v2User->birth_month : $v2User->birth_month) . "-" . ((int)$v2User->birth_day < 10 ? "0" . (int)$v2User->birth_day : $v2User->birth_day);
        } else {
            $dob = "1970-01-01";
        }
        $hireDate = $v2User->hire_date != '0000-00-00' ? date("Y-m-d", strtotime($v2User->hire_date)) : null;

        $data = [
            'first_name' => $v2User->first_name,
            'last_name' => $v2User->last_name,
            'email' => trim($v2User->email),
            'password' => $v2User->password,
            'password_confirmation' => $v2User->password,
            'organization_id' => $v3Program->organization_id,
            'user_status_id' => $v2User->user_state_id,
            // 'phone' => @$v2User->phone,
            'employee_number' => (int)$v2User->employee_number,
            'division' => $v2User->division_name,
            'office_location' => $v2User->office_geo_location,
            'position_title' => $v2User->position_title,
            'position_grade_level' => $v2User->position_grade,
            'supervisor_employee_number' => $v2User->supervisor_employee_number,
            'last_location' => $v2User->last_location,
            'dob' => $dob,
            'update_id' => $v2User->update_id,
            'created_at' => $v2User->created,
            'updated_at' => $v2User->updated,
            'activated' => $v2User->activated,
            'deactivated' => $v2User->deactivated,
            'last_login' => $v2User->last_login,
            'v2_parent_program_id' => $v2User->parent_program_id,
            // 'v2_account_holder_id' => $v2User->account_holder_id, //sync by "user_v2_users"
            'work_anniversary' => $hireDate,
            'email_verified_at' => $v2User->activated
        ];
        $formRequest = new UserRequest();
        $validator = Validator::make($data, $formRequest->rules());
        if ($validator->fails()) {
            throw new Exception($validator->errors()->toJson());
        }
        return User::createAccount($data);
    }

    public function migrateRoles(object $v2User, User $v3User, object $v2Program)
    {
        $v2UserRoles = $this->getV2UserRoles($v2User->account_holder_id, $v2Program->account_holder_id);

        if (!empty($v2UserRoles)) {
            $newProgramRoles = [];
            foreach ($v2UserRoles as $v2UserRole) {
                if ($v2Program->account_holder_id !== $v2UserRole->program_account_holder_id) {
                    // We are running by program, and the user role does not belong to this program
                    continue;
                }
                $v2RoleName = $this->getRoleNameFromV2RoleName($v2UserRole->role_name);

                if ($v2RoleName) {
                    $v3RoleId = Role::getIdByName($v2RoleName);
                    if (!$v3RoleId) {
                        throw new Exception(" - Role:\"{$v2RoleName}\" for user({$v2User->email}) not found in V3.\n");
                    }
                    $newProgramRoles[$v2UserRole->v3_program_id][] = $v3RoleId;
                }
            }

            if ($newProgramRoles) {
                $v3User->programs()->sync(array_keys($newProgramRoles), false);
                foreach ($newProgramRoles as $programId => $programRoles) {
                    $v3User->syncProgramRoles($programId, $programRoles);
                }
            }
        }
    }

    public function getV2UserRoles($v2_user_account_holder_id, $v2AccountHolderId): array
    {
        $sql = sprintf("
            SELECT
                `r`.*,
                `r`.`owner_id` AS `program_account_holder_id`,
                `rhu`.`users_id`,
                `rt`.`type` AS role_name,
                `p`.`name` AS `program_name`,
                `p`.`v3_program_id`
            FROM
                `roles_has_users` `rhu`
                LEFT JOIN `roles` `r` ON `rhu`.`roles_id`=`r`.`id`
                LEFT JOIN `role_types` `rt` ON `r`.`role_type_id`=`rt`.`id`
                LEFT JOIN `programs` `p` ON `p`.`account_holder_id`=`r`.`owner_id`
            WHERE
                `rhu`.`users_id`=%d",
            $v2_user_account_holder_id);
        $sql .= sprintf(' AND p.account_holder_id=%d', (int)$v2AccountHolderId);
        return $this->v2db->select($sql);
    }

    public function getRoleNameFromV2RoleName($v2RoleName): string
    {
        $newRoleName = '';
        if ($v2RoleName == 'Administration') {
            $newRoleName = 'Super Admin';
        } elseif ($v2RoleName == 'Administrator') {
            $newRoleName = $this->v2pid() ? 'Admin' : 'Super Admin';
        } elseif ($v2RoleName == 'Participant') {
            $newRoleName = 'Participant';
        } elseif ($v2RoleName == 'Limited Program Manager') {
            $newRoleName = 'Limited Manager';
        } elseif ($v2RoleName == 'Program Manager') {
            $newRoleName = 'Manager';
        } elseif (strpos($v2RoleName, 'Read Only') !== false) {
            $newRoleName = 'Read Only Manager';
        }
        return $newRoleName;
    }

    /**
     * @param object $v2User
     * @param Program $v3Program
     * @return User
     * @throws Exception
     */
    public function migrateOnlyUser(object $v2User, Program $v3Program): User
    {
        $this->printf("Starting migration of v2user: {$v2User->account_holder_id} with email: {$v2User->email}\n");

        $v3User = User::getByEmail($v2User->email);
        if ($v3User) {
            $v3User->update(['organization_id' => $v3Program->organization_id]);
        } else {
            $v3User = $this->createUser($v2User, $v3Program);
        }

        $this->syncUserAssoc($v2User, $v3User);
        if ($v2User->v3_user_id != $v3User->id) {
            $this->v2db->statement(sprintf("
                    UPDATE `users`
                    SET `v3_user_id`=%d
                    WHERE account_holder_id=%d;
            ", $v3User->id, $v2User->account_holder_id));
        }

        return $v3User;
    }

}
