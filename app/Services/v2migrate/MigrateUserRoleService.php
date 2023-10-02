<?php
namespace App\Services\v2migrate;

use App\Models\Role;
use Exception;

class MigrateUserRoleService extends MigrationService
{
    public $rolesCache = [];
    public int $count = 0;

    public function _getRoleNameFromV2RoleName( $v2RoleName ) {
        $newRoleName = 'Participant';
        if( $v2RoleName == 'Administration')    {
            $newRoleName = 'Super Admin';
        }   elseif( $v2RoleName == 'Administrator' ) {
            $newRoleName = $this->v2pid() ? 'Admin' : 'Super Admin';
        }   elseif( $v2RoleName == 'Participant' ) {
            $newRoleName = 'Participant';
        }   elseif( $v2RoleName == 'Limited Program Manager' ) {
            $newRoleName = 'Limited Manager';
        }   elseif( strpos($v2RoleName, 'Read Only' ) !== false ) {
            $newRoleName = 'Read Only Manager';
        }
        return $newRoleName;
    }

    public function __construct()
    {
        parent::__construct();
        $this->printSql = true;

        //Get count of user roles

        //Get Role count for all users.
        // SELECT roles_id, users_id, count(users_id) num_user_cout FROM `roles_has_users` GROUP BY `users_id` HAVING num_user_cout > 1 ORDER BY num_user_cout DESC;

        /**
         // SELECT * FROM `roles` JOIN roles_has_users rhu on rhu.roles_id = roles.id JOIN users on users.account_holder_id=rhu.users_id WHERE roles.`role_type_id` = 2;
         //Get managers from v2
         */
    }

    public function getV2UserRoles( $v2_user_account_holder_id )    {

        $sql = sprintf("SELECT `r`.*, `r`.`owner_id` AS `program_account_holder_id`, `rhu`.`users_id`, `rt`.`type` AS role_name, `p`.`name` AS `program_name`, `p`.`v3_program_id` FROM `roles_has_users` `rhu` LEFT JOIN `roles` `r` ON `rhu`.`roles_id`=`r`.`id` LEFT JOIN `role_types` `rt` ON `r`.`role_type_id`=`rt`.`id` LEFT JOIN `programs` `p` ON `p`.`account_holder_id`=`r`.`owner_id` WHERE `rhu`.`users_id`=%d", $v2_user_account_holder_id); //AND rt.type NOT LIKE 'Participant' AND rt.type NOT LIKE 'Program Manager'
        if( (int)$this->v2pid() > 0 )  {
            $sql .= sprintf(' AND p.account_holder_id=%d', (int)$this->v2pid());
        }
        $v2UserRoles = $this->v2db->select($sql);
        if( $this->isPrintSql() ) {
            $this->printf("SQL: %s\n", $sql);
        }
        return $v2UserRoles;
    }
    public function migrate($v2User, $v3User) {
    // public function migrate() {
        // $v2UserRoles = $this->getV2UserRoles(203610);
        // $v2User = new stdClass;
        $v2UserRoles = $this->getV2UserRoles($v2User->account_holder_id);

        // $v2UserRoles = $this->getV2UserRoles(204298);
        if( sizeof($v2UserRoles) > 0) {
            $this->printf(" - %d roles found for user %s. Preparing to import roles.\n",  sizeof($v2UserRoles), $v2User->email);
            $newProgramRoles = [];
            foreach( $v2UserRoles as $v2UserRole ) {
                if( $this->v2pid() && !$v2UserRole->v3_program_id )   {
                    throw new Exception(sprintf(" - Error: Program \"%s\" is not synched with V3. Skipping.\n",  $v2UserRole->program_name));
                    // throw new Exception("Error: Program \"{$v2UserRole->program_name}\" is not synched with V3. Aborting user migration process.\n");
                    continue; //TO BE REMOVED? since we do not want to go with incomplete user roles?
                }
                if( $this->v2pid() && $this->v2pid() !== $v2UserRole->program_account_holder_id) {
                    //We are running by program, and the user role does not belong to this program
                    continue;
                }
                $this->printf(" - Looking for role \"%s\" in v3.\n",  $v2UserRole->role_name);
                $v2RoleName = $this->_getRoleNameFromV2RoleName( $v2UserRole->role_name );

                if( isset($this->rolesCache[$v2UserRole->id]) )   {
                    $v3RoleId = $this->rolesCache[$v2UserRole->id];
                }   else {
                    $v3RoleId = Role::getIdByName($v2RoleName);
                    if( !$v3RoleId ) {
                        throw new Exception(sprintf(" - Role:\"%s\" for user(%s) not found in V3.\n", $v2RoleName, $v2User->email));
                        continue;
                    }
                    $this->rolesCache[$v2UserRole->id] = $v3RoleId;
                }

                if( $v3RoleId ) {
                    if( $this->v2pid() ) {
                        if( !isset( $newProgramRoles[$v2UserRole->v3_program_id] )) {
                            $newProgramRoles[$v2UserRole->v3_program_id] = [];
                        }
                        $newProgramRoles[$v2UserRole->v3_program_id][] = $v3RoleId;
                    }   else {
                        $v3User->syncRoles( [$v3RoleId] );
                    }
                }
            }

            if( (!$v3User->organization_id ||  $v3User->organization_id == 1000000000) ) {
                if( $this->v2pid() ) {
                    if( $v2User->parent_program_id ) {
                        $parentProgram = (new \App\Models\Program)->select(['id', 'organization_id'])->find( $v2User->parent_program_id );
                        if( $parentProgram ) {
                            $v3User->organization_id = $parentProgram->organization_id;
                            $v3User->save();
                            $this->printf(" - OrganizationID \"%s\" set for v3user:\"%d\" from \"%s\".\n", $v3User->organization_id, $v3User->id, "v2User->parent_program_id");
                        }
                    }
                }   else {
                    $v3User->organization_id = 1; //Super admin
                    $v3User->save();
                }
            }

            if( $this->v2pid() && $newProgramRoles ) {
                $v3User->programs()->sync( array_keys($newProgramRoles) );
                foreach($newProgramRoles as $programId => $programRoles) {
                    $v3User->syncProgramRoles($programId, $programRoles);
                    if( !$v3User->organization_id ||  $v3User->organization_id == 1000000000) {
                        $parentProgram = (new \App\Models\Program)->select(['id', 'organization_id'])->find( $programId );
                        if( $parentProgram ) {
                            $v3User->organization_id = $parentProgram->organization_id;
                            $v3User->save();
                            $this->printf(" - OrganizationID \"%s\" set for v3user:\"%d\" while synching programRoles\n", $v3User->organization_id, $v3User->id);
                        }
                    }
                }
                $this->printf("Program roles synced for v3user:%d.\n", $v3User->id);
            }
        }
    }
}
