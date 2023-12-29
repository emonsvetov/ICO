<?php
namespace App\Services\v2migrate;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Password;
use App\Http\Requests\UserRequest;
use App\Models\Program;
use App\Models\JournalEvent;
use Illuminate\Support\Facades\DB;
use Exception;

use App\Models\User;
use App\Models\UserV2User;
use App\Models\Role;

class MigrateUsersService extends MigrationService
{
    private MigrateUserRoleService $migrateUserRoleService;

    public $offset = 0;
    public $limit = 1;
    public $iteration = 0;
    public $count = 0;
    public bool $isPrintSql = false;
    public bool $resyncRoles = true;
    public array $v3UserCache = [];
    public array $v3ProgramCache = [];

    public function __construct(MigrateUserRoleService $migrateUserRoleService)
    {
        parent::__construct();
        $this->migrateUserRoleService = $migrateUserRoleService;
    }

    public function migrate( $options = [] )  {
        if( !empty( $options['program'] )) {
            $v2programs = array_filter($options['program'], function($p) { return ( is_numeric($p) && (int) $p > 0 ); });
            if( !$v2programs ) {
                throw new Exception("Invalid program argument.");
            }
            foreach( $v2programs as $v2program_account_holder_id) {
                $v3RootProgram = Program::where('v2_account_holder_id', $v2program_account_holder_id)->select('id', 'name')->first();
                // pr($v3Program->getFlatTree()->toArray());
                // exit;
                if( $v3RootProgram ) {
                    $v3Programs = Program::find($v3RootProgram->id)->descendantsAndSelf()->select('id', 'name', 'parent_id', 'path', 'v2_account_holder_id')->depthFirst()->get()->toTree();
                    // pr( $v3Programs->toArray() );
                    $this->recursivelyMigrateUsersByV3Programs($v3Programs);
                }
            }
            // exit;
            // $v3Programs = Program::whereIn('v2_account_holder_id', $v2programs)->get()->toTree();
            // $program->descendantsAndSelf()->get()->toTree()
            /**
             * Via v2Program Tree
             *
             */
            // $v2RootPrograms = (new MigrateProgramsService)->read_list_all_root_program_ids( ['program'=>$v2programs] );
            // pr(count($v2RootPrograms));
            // foreach( $v2RootPrograms as $v2RootProgram ) {
            //     $programs_tree = [];
            //     $children_heirarchy_list = (new MigrateProgramsService)->read_list_children_heirarchy(( int )$v2RootProgram->account_holder_id);
            //     $programs_tree = sort_programs_by_rank_for_view($programs_tree, $children_heirarchy_list);
            //     pr($programs_tree);
            // }

            // pr($programs);
        }
        $this->v2db->statement("SET SQL_MODE=''");
        // $this->migrateUserRoleService->migrate();
        // $this->migrateNonDuplicateUsers();
        $this->offset = $this->iteration = 0;
        $this->setDebug(true);
        // $this->migrateDuplicateUsers();
    }

    public function recursivelyMigrateUsersByV3Programs( $v3Programs ) {
        // pr( $v3Programs->toArray() );
        // exit;
        foreach( $v3Programs as $v3Program) {
            if( isset( $this->v3ProgramCache[$v3Program->v2_account_holder_id])) {
                continue;
            }
            $v2users = $this->v2_read_list_by_program($v3Program->v2_account_holder_id);
            $this->setv2pid($v3Program->v2_account_holder_id);
            $this->setv3pid($v3Program->id);
            foreach( $v2users as $v2user)   {
                $this->migrateSingleUserByV2V3ProgramIds($v2user, $v3Program->v2_account_holder_id, $v3Program->id);
            }
            if( !empty( $v3Program['children']) ) {
                $this->recursivelyMigrateUsersByV3Programs( $v3Program['children'] );
            }
            $this->v3ProgramCache[$v3Program->v2_account_holder_id] = $v3Program;
        }
    }

    public function migrateSingleUserByProgram($v2User, $v2Program)    {
        if( !$v2User || !$v2User->email || !$v2Program || !$v2Program->v3_program_id) {
            $this->printf("Required argument missing in MigrateUserService->migrateSingleUserByProgram().\n");
            return;
        }
        $this->setv2pid($v2Program->account_holder_id);
        $this->setv3pid($v2Program->v3_program_id);
        $v3User = $this->migrateSingleUser( $v2User );
    }

    public function migrateSingleUserByV2V3ProgramIds($v2User, $v2_program_holder_id, $v3_program_id)    {
        if( !$v2User || !$v2User->email || !$v2_program_holder_id || !$v3_program_id) {
            $this->printf("Required argument missing in MigrateUserService->migrateSingleUserByProgram().\n");
            return;
        }
        $this->setv2pid($v2_program_holder_id);
        $this->setv3pid($v3_program_id);
        $v3User = $this->migrateSingleUser( $v2User );
    }

    public function migrateSingleUserByV2Program($v2User, $v2Program)    {
        if( !$v2User || !$v2User->email || !$v2Program || !$v2Program->v3_program_id) {
            $this->printf("Required argument missing in MigrateUserService->migrateSingleUserByProgram().\n");
            return;
        }
        $this->setv2pid($v2Program->account_holder_id);
        $this->setv3pid($v2Program->v3_program_id);
        $v3User = $this->migrateSingleUser( $v2User );
    }

    public function syncUserAssoc($v2User, $v3User) {
        // pr($v3User);
        $userV2user = $v3User->v2_users()->where('v2_user_account_holder_id', $v2User->account_holder_id)->first();
        // pr($userV2user);
        if( $userV2user ) {
            // pr($userV2user->toArray());
            $this->printf(" -- userV2User assoc found for user v2:%d and v3:%s\n", $v2User->account_holder_id, $v3User->id);
            if( $userV2user->user_id != $v3User->id ) {
                //if it was saved previously with different v3:id
                $userV2user->user_id = $v3User->id;
                $userV2user->save();
            }
        }   else {
            $newAssoc = new UserV2User(['v2_user_account_holder_id' => $v2User->account_holder_id]);
            $v3User->v2_users()->save($newAssoc);
            $this->printf(" -- New userV2User assoc added for user v2:%d and v3:%s\n", $v2User->account_holder_id, $v3User->id);
        }
        if( $v3User->v2_account_holder_id )  { //This is confusing, make it null
            $v3User->v2_account_holder_id = null;
            $v3User->save();
        }
    }

    public function migrateSingleUser( $v2User ) {

        if( !$v2User || !$v2User->email ) return;

        $this->setDebug(true);

        $this->printf("* Starting migration of v2user:%d with email:%s\n", $v2User->account_holder_id, $v2User->email);

        $createUser = true;
        // if( $v2User->v3_user_id ) {
        //     $this->printf(" - The \"v3_user_id\" exists for user %s exists. \n -- Confirming.\n",  $v2User->email);
        //     $v3User = User::find( $v2User->v3_user_id );

        //     if( $v3User ) {
        //         $this->printf(" -- Confirmed - yes. Synching userV2user assoc now..\n");
        //         //Re-confirm with email id ??
        //         if( $v2User->email != $v3User->email )  {
        //             $this->printf(" -- \$v2User->email:%s does not match with \$v3User->email:%s. Not sure what to do? Update refernce column ids?\n", $v2User->email, $v3User->email);
        //         }
        //         $createUser = false;
        //     }   else {
        //         $this->printf("User not found with false positive v2User:v3_user_id. We'll need to create one later..\n");
        //     }
        // }   else {
        //     //We will try to find by 1. email 2. v2User:account_holder_id in user_v2_users table
        //     $v3User = User::where('email', $v2User->email)->first();
        //     if( $v3User ) {
        //         $createUser = false;
        //     }   else {
        //         $userV2user = UserV2User::where('v2_user_account_holder_id', $v2User->account_holder_id)->first();
        //         if( $userV2user ) {
        //             $v3User = $userV2user->user;
        //             if($v3User) {
        //                 $createUser = false;
        //             }
        //         }
        //     }
        // }
        // pr($v3User->toArray());
        //Create user if applies
        pr($v2User->account_holder_id);
        pr($v2User->email);
        $v3User = User::where('email', $v2User->email)->first();
        if( $v3User ) {
            $createUser = false;
            $userV2user = UserV2User::where('v2_user_account_holder_id', $v2User->account_holder_id)->first();
            if( !$userV2user ) {
                $this->syncUserAssoc($v2User, $v3User);
            }
        }
        if( $v3User )   {
            if( $v2User->v3_user_id !=  $v3User->id ) {
                $this->printf("v3_user_id mismatch found. v3_user_id id stored in v2 db is %d while v3 user id is %d. Fixing now..\n", $v2User->v3_user_id, $v3User->id);
                $this->v2db->statement(sprintf("UPDATE `users` SET `v3_user_id`=%d WHERE account_holder_id=%d;", $v3User->id, $v2User->account_holder_id));
            }
        }
        if( $createUser ) {
            $this->printf("Creating new user with email:%s\n", $v2User->email);
            $v3User = $this->createUser($v2User);
            $this->syncUserAssoc($v2User, $v3User);
            $this->v2db->statement(sprintf("UPDATE `users` SET `v3_user_id`=%d WHERE account_holder_id=%d;", $v3User->id, $v2User->account_holder_id));
            $this->printf(" - New User with email:%s created in v3.\n",  $v2User->email);
        }

        // return $v3User;

        // Update v2User reference field for v3Id
        if( !empty($v3User) && !$createUser ) {

            $this->syncUserAssoc($v2User, $v3User);

            if( !$v2User->v3_user_id || ($v2User->v3_user_id != $v3User->id) ) {
                // $this->addV2SQL(sprintf("UPDATE `users` SET `v3_user_id`=%d WHERE account_holder_id=%d;", $v3User->id, $v2User->account_holder_id));
                $this->v2db->unprepared( $this->v2db->raw(sprintf("UPDATE `users` SET `v3_user_id`=%d WHERE account_holder_id=%d;", $v3User->id, $v2User->account_holder_id)) );
            }
        }

        // Migrate roles if applies
        if( $v3User ) {
            $this->printf(" - Attempting to import user roles\n");
            if( $this->v2pid() ) {
                $this->migrateUserRoleService->setv2pid( $this->v2pid() );
            }
            $this->migrateUserRoleService->migrate($v2User, $v3User);

            $this->executeV2SQL();
            // $this->migrateUserJournalEvents($v2User, $v3User);
            // Migration Accounts Only. We will migration JournalEvents and related data in separate step below
            (new \App\Services\v2migrate\MigrateAccountsService)->migrateByModel($v3User, $v2User->account_holder_id);

            $this->executeV2SQL(); //run for any missing run!

            //Migration Journal events, postings, xml_event_data in this step. This step will work perfectly only if the Accounts are imported by calling "MigrateAccountsService" before running this "MigrateJournalEventsService"
            // pr($v3User->id);
            // exit;
            //## (new \App\Services\v2migrate\MigrateJournalEventsService)->migrateJournalEventsByModelAccounts($v3User); //NOT PULLING HERE. Rather pull using separate command.

            // $this->executeV2SQL(); //run for any missing run!

            // $this->fixUserJournalEvents( $v2User,  $v3User);
            // $this->fixUserXmlEvents( $v2User,  $v3User);
        }

        return $v3User;
    }

    public function createUser($v2User) {
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
            'employee_number' => (int) $v2User->employee_number,
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
            'hire_date' => $v2User->hire_date && $v2User->hire_date != '0000-00-00' ? $v2User->hire_date : null,
            'email_verified_at' => $v2User->activated
        ];
        $formRequest = new UserRequest();
        $validator = Validator::make($data, $formRequest->rules());
        if ($validator->fails()) {
            throw new Exception($validator->errors()->toJson());
        }
        return User::createAccount( $data );
    }

    private function tmpFunc_getRolesCountForUsers()  {
        $sql = sprintf("SELECT users_id, count(roles_id) AS roles_count FROM roles_has_users WHERE 1=1 GROUP BY roles_id HAVING roles_count > 1 ORDER BY roles_count DESC");
        $results = $this->v2db->select($sql);
        if( $this->isPrintSql() ) {
            $this->printf("SQL:%s\n", $sql);
        }
        return $results;//204298,244119
    }

    public function v2_read_list_by_program($v2_program_account_holder_id = 0, $role_types = [], $args=[]) {
		return $this->_get_users_with_roles ( $v2_program_account_holder_id, $role_types, $args);
	}
	/** _get_users_in_roles()
	 *
	 * Get a list of users within a program, given a list of roles
	 *
	 *
	 * @param int $program_account_holder_id
	 * @param string[] $role_types
	 * @return collection
     * */

	private function _get_users_with_roles($program_account_holder_id = 0, $role_types = array(), $args=[]) {
        $hierarchy = false;
		$role_type_count = count ( $role_types );
		$role_types_string = '';
		if (( int ) $role_type_count > 0 ) {
			for($x = 0; $x < $role_type_count; $x ++) {
				$role_types [$x] = "'" . $role_types [$x] . "'";
			}
			$role_types_string = implode ( ', ', $role_types );
		}

        $this->v2db->statement("SET SQL_MODE=''");

		$sql = "
			SELECT
                roles.owner_id program_id,
				users.*,
				CASE
				    WHEN `users`.phone THEN
				        CONCAT(ccc.code, `users`.phone)
                    ELSE ''
                END as phone,
				users.phone_confirmed,
				users.mfa_auth,
				p.title AS position,
				state_types.state AS user_state_name,
                (
                        SELECT award_level.name
                        FROM award_level
                        INNER JOIN award_levels_has_users ON award_levels_has_users.award_levels_id = award_level.id
                        WHERE
                            award_levels_has_users.users_id = users.account_holder_id
                        AND
                            award_level.program_account_holder_id = program_id
                ) as award_level,
				ue.employee_number,
				ue.location_id,
				ue.user_level
			FROM
				users
            LEFT JOIN
                `country_calling_codes` ccc on ccc.id = `users`.country_calling_code_id
			LEFT JOIN
				roles_has_users ON roles_has_users.users_id = users.account_holder_id
			LEFT JOIN
				roles ON roles.id = roles_has_users.roles_id
			LEFT JOIN
				role_types ON role_types.id = roles.role_type_id
			LEFT JOIN
				state_types ON state_types.id = users.user_state_id
		    LEFT JOIN award_levels_has_users ON award_levels_has_users.users_id = users.account_holder_id
			LEFT JOIN award_level ON award_level.id = award_levels_has_users.award_levels_id AND award_level.program_account_holder_id = {$program_account_holder_id}
			LEFT JOIN users_extended_info AS ue ON ue.user_id = users.account_holder_id
			LEFT JOIN (
				SELECT pl.title, pa.user_id FROM position_levels AS pl
				INNER JOIN position_assignment AS pa ON pa.position_level_id = pl.id AND pa.program_id = {$program_account_holder_id} AND pl.status = 1
			) p ON p.user_id = users.account_holder_id
			WHERE
		";

        $sql = $sql . " " . $this->_where_in_program ( $hierarchy, $program_account_holder_id );

		if(isset($args['user_state_id']) && !empty($args['user_state_id'])){
			$user_state_ids = is_array($args['user_state_id']) ? implode(',',$args['user_state_id']) : $args['user_state_id'];
			$sql = $sql . " AND users.user_state_id IN ({$user_state_ids}) ";
		}

		if(isset($args['active']) && $args['active'] == true){
			$sql = $sql . " AND users.user_state_id = 2 ";
		}

        if( $role_types_string != "" )   {
            $sql .= " AND role_types.type IN ({$role_types_string})";
        }

		$sql = $sql . "
			GROUP BY
				users.account_holder_id
		;";

        if( $this->isPrintSql())    {
            $this->printf("SQL: %s\n", $sql);
        }

		return $this->v2db->select( $sql );
	}

    private function _where_in_program($hierarchy = false, $program_id = 0, $all = false) {
		$sql = "";
		if ($hierarchy) {
			if (! $all) {
				$sql = $sql . "
				roles.owner_id in (
					select descendant as program_id
				from program_paths
				where
                program_paths.ancestor = (
						select ancestor
						from program_paths parent
						where
							parent.descendant = {$program_id}
							and path_length = 1
					)
					and path_length > 0)
				";
			}
		} else {
			$sql = $sql . "
				roles.owner_id = {$program_id}
			";
		}
		if ( $all ) {
			// throw new RuntimeException("BCM HERE 5 - NOT HERE");
			$root_program_id = resolve(\App\Services\v2migrate\MigrateProgramsService::class)->get_top_level_program_id ( $program_id );
			$sql = "
				program_paths.ancestor in (select descendant from program_paths where ancestor = {$root_program_id})
			";
		}
		return $sql;
	}

    public function migrateSuperAdmins( $v2Program = null, $v3Program = null )    {
        //SELECT users.account_holder_id, users.email, users.v3_user_id, roles.*, roles_has_users.* FROM `users` JOIN `roles_has_users` ON users.account_holder_id = `roles_has_users`.users_id JOIN roles on roles.id= roles_has_users.roles_id WHERE roles.owner_id = 1;

        $sql = sprintf("SELECT users.* FROM `users` JOIN `roles_has_users` ON users.account_holder_id = `roles_has_users`.users_id JOIN roles on roles.id= roles_has_users.roles_id WHERE roles.owner_id = 1");

        $users = $this->v2db->select( $sql );
        if( ($count = sizeof($users)) > 0) {
            $this->printf("Found %d admins to be migrated\n", $count);
            foreach ($users as $user) {
                // if( !$user->account_holder_id != 719107) {
                //     continue;
                // }
                $this->printf("Migrating user with email %s, times\n", $user->email);
                $this->setv2pid(null);
                $v3User = $this->migrateSingleUser( $user );
            }
            return $users;
        }
    }

    public function fixUserJournalEvents($v2User, $v3User)  {
        if( !$v3User ) return;
        $journalEvents = \App\Models\JournalEvent::where('prime_account_holder_id', (int) ($this->idPrefix . $v2User->account_holder_id))->get();
        if( $journalEvents )    {
            foreach( $journalEvents as $journalEvent)   {
                $journalEvent->prime_account_holder_id = $v3User->account_holder_id;
                $journalEvent->save();
            }
        }
    }

    public function fixUserXmlEvents($v2User, $v3User)  {
        if( !$v3User ) return;
        $xmlEvents = \App\Models\EventXmlData::where('awarder_account_holder_id', (int) ($this->idPrefix . $v2User->account_holder_id))->get();
        if( $xmlEvents )    {
            foreach( $xmlEvents as $xmlEvent)   {
                $xmlEvent->awarder_account_holder_id = $v3User->account_holder_id;
                $xmlEvent->save();
                $this->printf(' -- Fixed xmlEvent:%s for user:%s\n', $xmlEvent->id, $v3User->id);
            }
        }
    }
}
