<?php
namespace App\Services\v2migrate;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Password;
use App\Http\Requests\UserRequest;
use Illuminate\Support\Facades\DB;
use Exception;

use App\Models\User;
use App\Models\Role;

class MigrateUsersService extends MigrationService
{
    public $offset = 0;
    public $limit = 1;
    public $iteration = 0;
    public $count = 0;
    public bool $printSql = false;
    public bool $resycnRoles = true;
    public $rolesCache = [];

    public function __construct()
    {
        parent::__construct();
    }

    public function migrate()  {
        $this->v2db->statement("SET SQL_MODE=''");
        $this->migrateUsers();
        echo "Migrate users start!";
    }

    public function getUsersByDuplicateEmail() {
        print("Getting users by duplicate email address\n");
        $users = $this->v2db->select("SELECT account_holder_id, email, count(email) email_count FROM users WHERE (email != '' AND email IS NOT NULL) GROUP BY email HAVING email_count >1");
        if( ($count = sizeof($users)) > 0) {
            printf("Found %d users by duplicate email address\n", $count);
            foreach ($users as $user) {
                printf(" -- %s %d times\n", $user->email, $user->email_count);
            }
        }
    }

    public function migrateUsers() {
        $this->iteration++;
        $users = $this->getNonDuplicateUsersIdentifiedByEmail();
        if( !$users ) {
            printf("No user found in iteration %d", $this->iteration);
            return;
        }
        foreach( $users as $v2User) {
            try {
                $this->migrateSingleUser($v2User);
                DB::commit();
                $this->v2db->commit();
                if( $this->count >= 1 ) exit;
            } catch(Exception $e) {
                print($e->getMessage());
                DB::rollback();
                $this->v2db->rollBack();
                exit;
            }
            printf("--------------------------------------------------\n");
        }
        if( count($users) >= $this->limit) {
            $this->offset = $this->offset + $this->limit;
            $this->migrateUsers();
        }
    }

    public function getNonDuplicateUsersIdentifiedByEmail() {
        $sql = sprintf("SELECT account_holder_id, v3_user_id, email, first_name, last_name, employee_number, division_name, position_title, position_grade, created, updated, count(email) email_count, supervisor_employee_number, last_location, last_login, birth_month, birth_day, user_state_id, update_id, activated, deactivated, parent_program_id, password, office_geo_location, hire_date FROM users WHERE (email != '' AND email IS NOT NULL) AND (v3_user_id IS NULL OR v3_user_id = 0 )  GROUP BY email HAVING email_count = 1 LIMIT %d, %d", $this->offset, $this->limit);
        $v2Users = $this->v2db->select($sql);
        if($this->isPrintSql()) {
            printf("SQL:%s\n", $sql);
        }

        return $v2Users;
    }
    public function getV2UserRoles( $v2_user_account_holder_id )    {
        $sql = sprintf("SELECT `r`.*, `r`.`owner_id` AS `program_account_holder_id`, `rhu`.`users_id`, `rt`.`type` AS role_name, `p`.`name` AS `program_name`, `p`.`v3_program_id` FROM `roles_has_users` `rhu` LEFT JOIN `roles` `r` ON `rhu`.`roles_id`=`r`.`id` LEFT JOIN `role_types` `rt` ON `r`.`role_type_id`=`rt`.`id` LEFT JOIN `programs` `p` ON `p`.`account_holder_id`=`r`.`owner_id` WHERE `rhu`.`users_id`=%d", $v2_user_account_holder_id);
        $v2UserRoles = $this->v2db->select($sql);
        if( $this->isPrintSql() ) {
            printf("SQL:%s\n", $sql);
        }
        return $v2UserRoles;
    }
    public function createUser($v2User) {
        $v3User = User::where('email', $v2User->email)->first();
        if( !$v3User ) {
            // pr($v2User);
            printf(" - User with email %s does not exist in v3. Preparing to import..\n",  $v2User->email);
            if( (int)$v2User->birth_month && (int)$v2User->birth_day) {
                $dob = "1970-" . ((int)$v2User->birth_month < 10 ? "0" . (int)$v2User->birth_month :  $v2User->birth_month) . "-" . ( (int) $v2User->birth_day < 10 ? "0" . (int)$v2User->birth_day :  $v2User->birth_day);
            }   else {
                $dob = "1970-01-01";
            }
            $data = [
                'first_name' => $v2User->first_name,
                'last_name' => $v2User->last_name,
                'email' => trim($v2User->email),
                'password' => $v2User->password,
                'password_confirmation' => $v2User->password,
                'organization_id' => 1000000000, //TODO
                'user_status_id' => $v2User->user_state_id,
                // 'phone' => @$v2User->phone,
                'employee_number' => $v2User->employee_number,
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
                'v2_account_holder_id' => $v2User->account_holder_id,
                'hire_date' => $v2User->hire_date,
            ];
            $formRequest = new UserRequest();
            $validator = Validator::make($data, $formRequest->rules());
            if ($validator->fails()) {
                throw new Exception($validator->errors()->toJson());
            }
            return User::createAccount( $data );
        } else {
            printf(" - User with email %s exists in v3. Skipping.",  $v2User->email);
        }
    }
    public function migrateSingleUser( $v2User) {
        if( !$v2User || !$v2User->email ) return;

        printf("* Starting migration of user:%d with email:%s\n", $v2User->account_holder_id,$v2User->email);
        $isNewUser = false;
        if( $v2User->v3_user_id ) {
            printf(" - User with email %s exist in v3.\n",  $v2User->email);
            //TODO - Check for the update??
            $v3User = User::find( $v2User->v3_user_id );
            // return;
        }   else {
            $v3User = $this->createUser($v2User);
            printf(" - New User with email %s created in v3.\n",  $v2User->email);
            $this->v2db->statement(sprintf("UPDATE `users` SET `v3_user_id`=%d WHERE account_holder_id=%d", $v3User->id, $v2User->account_holder_id));
            $isNewUser = true;
        }

        if( $v3User ) {
            if( $isNewUser || $this->resycnRoles ) {
                $v2UserRoles = $this->getV2UserRoles($v2User->account_holder_id);
                // $v2UserRoles = $this->getV2UserRoles(204298);
                // pr($v2UserRoles);
                if( sizeof($v2UserRoles) > 0) {
                    printf(" - Roles for user %s found. Preparing to create new roles.\n",  $v2User->email);
                    $newProgramRoles = [];
                    foreach( $v2UserRoles as $v2UserRole ) {
                        if( !$v2UserRole->v3_program_id )   {
                            printf(" - Error: Program \"{$v2UserRole->program_name}\" is not synched with V3. juming to next user.\n",  $v2User->email);
                            // throw new Exception("Error: Program \"{$v2UserRole->program_name}\" is not synched with V3. Aborting user migration process.\n");
                            continue; //TO BE REMOVED since we do not want to go with incomplete user roles
                        }
                        $v2RoleName = $v2UserRole->role_name;
                        printf(" - Looking for role \"%s\" in v3.\n",  $v2RoleName);
                        switch ($v2RoleName) {
                            case Role::ROLE_PARTICIPANT:
                                continue 2;
                                if( isset($this->rolesCache[$v2UserRole->id]) )   {
                                    $v3RoleId = $this->rolesCache[$v2UserRole->id];
                                }   else {
                                    $v3RoleId = Role::getIdByName($v2RoleName);
                                    if( !$v3RoleId ) {
                                        printf("Role:\"%s\" for user(%s) not found in V3.\n", $v2RoleName, $v2User->email);
                                        continue 2;
                                    }
                                    $this->rolesCache[$v2UserRole->id] = $v3RoleId;
                                }

                                if( $v3RoleId ) {
                                    if( !isset( $newProgramRoles[$v2UserRole->v3_program_id] )) {
                                        $newProgramRoles[$v2UserRole->v3_program_id] = [];
                                    }
                                    $newProgramRoles[$v2UserRole->v3_program_id][] = $v3RoleId;
                                }

                                // pr($v3RoleId);
                                break;

                            default:
                                // throw new Exception("Error: Program \"{$v2UserRole->program_name}\" is not synched with V3. Aborting user migration process.\n");
                                printf("Unknown role found. Aborting..\n");
                                break;
                        }
                    }
                    if( $newProgramRoles ) {
                        pr($newProgramRoles);
                        exit;
                        $v3User->programs()->sync( array_keys($newProgramRoles) );
                        foreach($newProgramRoles as $programId => $programRoles) {
                            $v3User->syncProgramRoles($programId, $programRoles);
                        }
                        printf("Program roles synced for v3user:%d.\n", $v3User->id);
                        $this->count++;
                    }
                }
            }
        }
    }
    private function isPrintSql() {
        return $this->printSql;
    }
    private function tmpFunc_getRolesCountForUsers()  {
        $sql = sprintf("SELECT users_id, count(roles_id) AS roles_count FROM roles_has_users WHERE 1=1 GROUP BY roles_id HAVING roles_count > 1 ORDER BY roles_count DESC");
        $results = $this->v2db->select($sql);
        if( $this->isPrintSql() ) {
            printf("SQL:%s\n", $sql);
        }
        return $results;//204298,244119


    }
}
