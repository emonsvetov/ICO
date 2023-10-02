<?php
namespace App\Services\v2migrate;

use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Password;
use App\Http\Requests\UserRequest;
use App\Models\EventXmlData;
use App\Models\JournalEvent;
use Illuminate\Support\Facades\DB;
use Exception;

use App\Models\User;
use App\Models\Role;

class MigrateUsersService extends MigrationService
{
    private MigrateUserRoleService $migrateUserRoleService;

    public $offset = 0;
    public $limit = 1;
    public $iteration = 0;
    public $count = 0;
    public bool $printSql = false;
    public bool $resyncRoles = true;
    public array $v3UserCache = [];

    public function __construct(MigrateUserRoleService $migrateUserRoleService)
    {
        parent::__construct();
        $this->migrateUserRoleService = $migrateUserRoleService;
    }

    public function migrate()  {
        $this->v2db->statement("SET SQL_MODE=''");
        // $this->migrateUserRoleService->migrate();
        // $this->migrateNonDuplicateUsers();
        $this->offset = $this->iteration = 0;
        $this->setDebug(true);
        $this->migrateDuplicateUsers();
    }

    private function getDuplicateUsersIdentifiedByEmail() {
        $this->printf("Finding users by duplicate email addresses, iteration:%s\n", ++$this->iteration);
        $sql = sprintf("SELECT account_holder_id, email, count(email) email_count FROM users WHERE (email != '' AND email IS NOT NULL) GROUP BY email HAVING email_count > 1 LIMIT %d, %d", $this->offset, $this->limit);
        $users = $this->v2db->select( $sql );
        if( ($count = sizeof($users)) > 0) {
            $this->printf("Found %d users by duplicate email address\n", $count);
            foreach ($users as $user) {
                $this->printf(" -- %s, %d times\n", $user->email, $user->email_count);
            }
            return $users;
        }
    }

    private function getNonDuplicateUsersIdentifiedByEmail() {
        $sql = sprintf("SELECT account_holder_id, v3_user_id, email, first_name, last_name, employee_number, division_name, position_title, position_grade, created, updated, count(email) email_count, supervisor_employee_number, last_location, last_login, birth_month, birth_day, user_state_id, update_id, activated, deactivated, parent_program_id, password, office_geo_location, hire_date FROM users WHERE (email != '' AND email IS NOT NULL) AND (v3_user_id IS NULL OR v3_user_id = 0 ) GROUP BY email HAVING email_count = 1 LIMIT %d, %d", $this->offset, $this->limit);
        $v2Users = $this->v2db->select($sql);

        $this->printf("SQL:%s\n", $sql);

        return $v2Users;
    }
    public function migrateDuplicateUsers() {
        $users = $this->getDuplicateUsersIdentifiedByEmail();
        if( !$users ) {
            $this->printf("No user found in iteration %d\n", $this->iteration);
            return;
        }
        foreach( $users as $v2User) {
            try {
                $this->migrateSingleDuplicateUser($v2User);
                // DB::commit();
                // $this->v2db->commit();
                // if( $this->count >= 1 ) exit;
            } catch(Exception $e) {
                print($e->getMessage());
                // DB::rollback();
                // $this->v2db->rollBack();
            }
            $this->printf("-------------\n");
        }
        $this->executeV2SQL(); //execute if any
        $this->executeV3SQL(); //execute if any
        $this->printf("-------------------------------------------------\n");
        if( count($users) >= $this->limit) {
            $this->offset = $this->offset + $this->limit;
            $this->migrateDuplicateUsers();
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

    public function migrateSingleUser( $v2User ) {

        if( !$v2User || !$v2User->email ) return;

        $this->setDebug(true);

        $this->printf("* Starting migration of user:%d with email:%s\n", $v2User->account_holder_id,$v2User->email);

        $isNewUser = false;
        $createUser = true;
        if( $v2User->v3_user_id ) {
            $this->printf(" - The \"v3_user_id\" exists for user %s exists.\n",  $v2User->email);
            $this->printf(" -- Now making sure that user {%s} exists in v3.\n",  $v2User->email);
            $v3User = User::find( $v2User->v3_user_id );
            if( $v3User ) {
                //TODO - Check for the update??
                $this->printf(" -- User {%s} EXISTS! exists in v3. Skipping..\n",  $v2User->email);
                $createUser = false; //if need to go further use this
                // return;
            }   else {
                $createUser = true;
            }
            // return;
        }
        //Create user if applies
        if( $createUser ) {
            $v3User = User::where( function ($query) use ($v2User) {
                $query->orWhere('v2_account_holder_id', $v2User->account_holder_id);
                $query->orWhere('email', $v2User->email);
            } )->first();
            if( !$v3User ) {
                $this->printf("Ready to create new user with email:%s\n", $v2User->email);
                $v3User = $this->createUser($v2User);
                $this->importMap['users'][$v3User->id] = $v3User;
                $this->printf(" - New User with email:%s created in v3.\n",  $v2User->email);
                $isNewUser = true;
            }   else {
                $this->printf(" - User exists in v3 by \"email:%s\" or \"v2_account_holder_id:%d\".\n",  $v3User->email, $v3User->v2_account_holder_id);
            }
        }

        // return $v3User;

        // Update v2User reference field for v3Id
        if( !$v2User->v3_user_id ) {
            $this->addV2SQL(sprintf("UPDATE `users` SET `v3_user_id`=%d WHERE account_holder_id=%d;", $v3User->id, $v2User->account_holder_id));
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
            // (new \App\Services\v2migrate\MigrateAccountsService)->migrateByModel($v3User);

            $this->executeV2SQL(); //run for any missing run!

            //Migration Journal events, postings, xml_event_data in this step. This step will work perfectly only if the Accounts are imported by calling "MigrateAccountsService" before running this "MigrateJournalEventsService"
            // pr($v3User->id);
            // exit;
            // (new \App\Services\v2migrate\MigrateJournalEventsService)->migrateJournalEventsByModelAccounts($v3User);

            $this->executeV2SQL(); //run for any missing run!

            // $this->fixUserJournalEvents( $v2User,  $v3User);
            $this->fixUserXmlEvents( $v2User,  $v3User);
        }

        return $v3User;
    }

    // public function migrateUserJournalEvents($v2User, $v3User)  {
    //     if( !$v2User->v3_user_id || !$v3User->v2_account_holder_id ) {
    //         $this->printf("Required argument missing in MigrateUserService->migrateUserJournalEvents().\n");
    //         return;
    //     }

    //     //Migrate Journal Events for User
    //     $this->printf("Attempt to import user journal events\n");
    //     printf(" - Fetching journal_events for user:\"%s\"\n", $v2User->email);

    //     (new \App\Services\v2migrate\MigrateJournalEventsService)->migrateByUser($v2User, $v3User, null);
    //     // pr($v2User);
    //     // pr($v3User);

    // }

    // public function recursivelyMigrateUserJournalEvents( $v2User, $v3User, $parent_id = null )  {
    //     $sql = sprintf("SELECT *, `id` AS `journal_event_id` FROM `journal_events` WHERE `prime_account_holder_id`=%d", $v2User->account_holder_id);
    //     if( !$parent_id )    {
    //         $sql .= " AND `parent_journal_event_id` IS NULL";
    //     }   else {
    //         $sql .= sprintf(" AND `parent_journal_event_id`=%d", $parent_id);
    //     }
    //     $journalEvents = $this->v2db->select($sql);
    //     // pr($journalEvents[0]);
    //     // exit;
    //     if( $journalEvents )    {
    //         foreach( $journalEvents as $row )  {
    //             $create = true;
    //             // pr($row);
    //             if( $row->v3_journal_event_id )    { //find by v3 id
    //                 $existing = JournalEvent::find( $row->v3_journal_event_id );
    //                 // pr($existing->toArray());
    //                 if( $existing )   {
    //                     printf(" - Journal Event \"%d\" exists for v2: \"%d\"\n", $existing->id, $row->journal_event_id);
    //                     $create = false;
    //                     //Update??
    //                 }
    //             }   else {
    //                 //find by v2 id
    //                 $existing = JournalEvent::where('v2_journal_event_id', $row->journal_event_id )->first();
    //                 if( $existing )   {
    //                     printf(" - Journal Event \"%d\" exists for v2: \"%d\", found via v2_journal_event_id search. Updating null v3_journal_event_id value.\n", $existing->id, $row->v3_journal_event_id, $row->journal_event_id);
    //                     $this->addV2SQL(sprintf("UPDATE `journal_events` SET `v3_journal_event_id`=%d WHERE `id`=%d", $existing->id, $row->journal_event_id));
    //                     $create = false;
    //                     //Update??
    //                 }
    //             }
    //             $newV3JournalEvent = null;
    //             if( $create )   {
    //                 $newV3JournalEvent = JournalEvent::create(
    //                     [
    //                         'v2_journal_event_id' => $row->journal_event_id,
    //                         'prime_account_holder_id' => $v2User->account_holder_id,
    //                         'v2_prime_account_holder_id' => $row->prime_account_holder_id,
    //                         'journal_event_type_id' => $row->journal_event_type_id,
    //                         'notes' => $row->notes,
    //                         'event_xml_data_id' => $row->event_xml_data_id,
    //                         'invoice_id' => $row->invoice_id,
    //                         'parent_id' => $parent_id,
    //                         'v2_parent_journal_event_id' => $row->parent_journal_event_id,
    //                         'is_read' => $row->is_read,
    //                         'created_at' => $row->journal_event_timestamp
    //                     ]
    //                 );

    //                 //Add to import map
    //                 $this->importMap['users'][$v3User->id]['journalEvents'][$newV3JournalEvent->id] = $newV3JournalEvent;

    //                 if( $newV3JournalEvent ) {
    //                     printf(" - New Journal Event \"%d\" created for v2 journal event \"%d\"\n",$newV3JournalEvent->id, $row->journal_event_id);
    //                     $this->v2db->statement(sprintf("UPDATE `journal_events` SET `v3_journal_event_id`=%d WHERE `id`=%d", $newV3JournalEvent->id, $row->journal_event_id));
    //                 }
    //             }

    //             $current = $existing ? $existing : $newV3JournalEvent;

    //             if( (int) $row->event_xml_data_id > 0 )   {
    //                 $existing = EventXmlData::where('v2_id', $row->event_xml_data_id)->first();
    //                 if( $existing ) {
    //                     //exists
    //                 }   else {
    //                     $sql = sprintf("SELECT * FROM `event_xml_data` WHERE `id`=%d", $row->event_xml_data_id);
    //                     $results = $this->v2db->select($sql);
    //                     if( sizeof($results) > 0 )  {
    //                         $eventXmlData = current($results); //just one!

    //                         //Get Awarderer
    //                         if( (int) $eventXmlData->awarder_account_holder_id > 0 )  {
    //                             if( $eventXmlData->awarder_account_holder_id == $v2User->account_holder_id) {
    //                                 $awarder_account_holder_id = $v3User->account_holder_id;
    //                             }   else {
    //                                 $v3User_tmp = User::where('v2_account_holder_id', $eventXmlData->awarder_account_holder_id)->first();
    //                                 if( $v3User_tmp )   {
    //                                     $awarder_account_holder_id = $v3User_tmp->account_holder_id;
    //                                 }   else {
    //                                     //User not imported, but we wont import it here otherwise we fall in a recursive loop, rather save user id with prefix of 999999999 so we can later pull it if required.
    //                                     $awarder_account_holder_id = (int) ("10000000001".$eventXmlData->awarder_account_holder_id);
    //                                 }
    //                             }
    //                         }

    //                         //Get eventID
    //                         $v3EventId = 0;
    //                         if( (int) $eventXmlData->event_template_id > 0 )   {
    //                             $v3Event_tmp = \App\Models\Event::where('v2_event_id', $eventXmlData->event_template_id)->first();
    //                             if( $v3Event_tmp )  {
    //                                 $v3EventId = $v3Event_tmp->id;
    //                             }   else {
    //                                 $v3EventId = (int) ("33333333333".$eventXmlData->event_template_id); //prefix of 33333333333 so we can later pull it if required.
    //                             }
    //                         }

    //                         $v3XEDId = EventXmlData::insertGetId([
    //                             'v2_id' => $eventXmlData->id,
    //                             'awarder_account_holder_id' => $awarder_account_holder_id,
    //                             'name' => $eventXmlData->name,
    //                             'award_level_name' => $eventXmlData->award_level_name,
    //                             'amount_override' => $eventXmlData->amount_override,
    //                             'notification_body' => $eventXmlData->notification_body,
    //                             'notes' => $eventXmlData->notes,
    //                             'referrer' => $eventXmlData->referrer,
    //                             'email_template_id' => "2222222222".$eventXmlData->email_template_id,
    //                             'event_type_id' => $eventXmlData->event_type_id,
    //                             'event_template_id' => $v3EventId,
    //                             'icon' => $eventXmlData->icon,
    //                             'xml' => $eventXmlData->xml,
    //                             'award_transaction_id' => $eventXmlData->award_transaction_id,
    //                             'lease_number' => $eventXmlData->lease_number,
    //                             'token' => $eventXmlData->token
    //                         ]);
    //                     }
    //                 }
    //             }

    //             $this->executeV2SQL();

    //             $this->recursivelyMigrateUserJournalEvents( $v2User, $v3User, $row->journal_event_id );
    //         }
    //     }
    // }

    public function migrateSingleDuplicateUser( $v2DuplicateUser )    {
        $sql = sprintf("SELECT u.* FROM `users` u WHERE `email`='%s'", $v2DuplicateUser->email);
        $v3Users = $this->v2db->select($sql);
        $this->printf($sql . "\n");
        $this->printf("Found %d users with email:%s\n", count($v3Users), $v2DuplicateUser->email);
        $isNewUser = false;
        if( $v3Users ) {
            foreach( $v3Users as $v2User )  {
                if( $v2User->v3_user_id ) {
                    $this->printf(" - User with email %s exist in v3.\n",  $v2User->email);
                    //TODO - Check for the update??
                    $v3User = User::find( $v2User->v3_user_id );
                }   else {
                    $v3User = User::where( 'v2_account_holder_id', $v2User->account_holder_id )->orWhere('email', $v2User->email)->first();
                    if( !$v3User ) {
                        $this->printf("Ready to create new user with email:%s\n", $v2User->email);
                        // continue;
                        $v3User = $this->createUser($v2User);
                        $this->printf(" - New User with email:%s created in v3.\n",  $v2User->email);
                        $isNewUser = true;
                    }   else {
                        $this->printf(" - User exists in v3 by \"email:%s\" or \"v2_account_holder_id:%d\".\n",  $v3User->email, $v3User->v2_account_holder_id);
                    }
                }

                // Update v2User reference field for v3Id
                if( !$v2User->v3_user_id ) {
                    $this->printf("\$v2User->v3_user_id is null for user:%s. Updating..\n", $v2User->email);
                    $this->addV2SQL(sprintf("UPDATE `users` SET `v3_user_id`=%d WHERE account_holder_id=%d;", $v3User->id, $v2User->account_holder_id));
                }

                // Migrate roles if applies
                if( $v3User ) {
                    if( $isNewUser || $this->resyncRoles ) {
                        $this->migrateUserRoleService->migrate($v2User, $v3User);
                    }
                }
            }
        }
        // pr(count($roles));
    }

    // public function migrateSingleDuplicateUser__theRolesWay( $v2DuplicateUser )    {
    //     $sql = sprintf("SELECT u.account_holder_id, u.v3_user_id, u.email, rt.type, r.owner_id as v2_program_id, p.name as program_name, p.v3_program_id FROM `users` u JOIN roles_has_users rhu on rhu.users_id = u.account_holder_id JOIN roles r on r.id=rhu.roles_id JOIN role_types rt on rt.id=r.role_type_id JOIN programs p on p.account_holder_id=r.owner_id WHERE `email`='apimentel@temp-tuckahoeholdings.com' AND p.v3_program_id IS NOT NULL", $v2DuplicateUser->email);
    //     $userWithRoles = $this->v2db->select($sql);
    //     $this->printf("Found %d roles for user with email:%s\n", count($userWithRoles), $v2DuplicateUser->email);
    //     if( $userWithRoles ) {
    //         foreach( $userWithRoles as $v2User )  {
    //             if( $v2User->v3_user_id ) {
    //                 $this->printf(" - User with email %s exist in v3.\n",  $v2User->email);
    //                 //TODO - Check for the update??
    //                 if( !isset( $this->v3UserCache[$v2User->account_holder_id]) ) {
    //                     $this->v3UserCache[$v2User->account_holder_id] = User::find( $v2User->v3_user_id );
    //                 }
    //                 $v3User = $this->v3UserCache[$v2User->account_holder_id];
    //             }   else {
    //                 $v3User = User::where( 'v2_account_holder_id', $v2User->account_holder_id )->orWhere('email', $v2User->email)->first();
    //                 if( !$v3User ) {
    //                     $v3User = $this->createUser($v2User);
    //                     $this->v3UserCache[$v2User->account_holder_id] = $v3User;
    //                     $this->printf(" - New User with email %s created in v3.\n",  $v2DuplicateUser->email);
    //                     $isNewUser = true;
    //                 }   else {
    //                     $this->v3UserCache[$v2User->account_holder_id] = $v3User;
    //                     $this->printf(" - User with email %s found in v3 by \"email\" or \"v2_account_holder_id\".\n",  $v2User->email);
    //                 }
    //             }
    //         }
    //     }
    //     // pr(count($roles));
    // }

    public function migrateNonDuplicateUsers() {
        $this->iteration++;
        $users = $this->getNonDuplicateUsersIdentifiedByEmail();
        if( !$users ) {
            $this->printf("No user found in iteration %d\n", $this->iteration);
            return;
        }
        foreach( $users as $v2User) {
            try {
                $this->migrateSingleUser($v2User);
                // DB::commit();
                // $this->v2db->commit();
                // if( $this->count >= 1 ) exit;
            } catch(Exception $e) {
                print($e->getMessage());
                // DB::rollback();
                // $this->v2db->rollBack();
            }
            $this->printf("--------------------------------------------------\n");
        }

        $this->executeV2SQL(); //execute if any
        $this->executeV3SQL(); //execute if any

        if( count($users) >= $this->limit) {
            $this->offset = $this->offset + $this->limit;
            $this->migrateNonDuplicateUsers();
        }
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
            'v2_account_holder_id' => $v2User->account_holder_id,
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
		if ($all) {
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
