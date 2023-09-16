<?php
namespace App\Services\v2migrate;

use Illuminate\Support\Facades\DB;
use RuntimeException;
use Exception;

use App\Services\v2migrate\Trait\CreateProgramTrait;
use App\Events\OrganizationCreated;
use App\Services\ProgramService;
use App\Models\Organization;
use App\Models\JournalEvent;
use App\Models\Program;
use App\Models\Account;
use App\Models\Posting;

class MigrateProgramsService extends MigrationService
{
    private ProgramService $programService;
    private MigrateProgramAccountsService $migrateProgramAccountsService;
    private $migrateUserService;

    use CreateProgramTrait;

    public $offset = 0;
    public $limit = 9999;
    public $iteration = 0;
    public $count = 0;
    public bool $overwriteProgram = false;
    public int $importedProgramsCount = 0;
    public array $importedPrograms = [];
    public array $importMap = []; //This is the final map of imported objects with name is key. Ex. $importMap['program'][$v2_account_holder_id] = $v2ID;
    public array $cacheJournalEventsMap = [];
    public bool $printSql = true;

    public function __construct(ProgramService $programService, MigrateProgramAccountsService $migrateProgramAccountsService)
    {
        parent::__construct();
        $this->programService = $programService;
        $this->migrateProgramAccountsService = $migrateProgramAccountsService;
        $this->migrateUserService = app('App\Services\v2migrate\MigrateUsersService');
    }

    public function migrate( $args = [] ) {

        printf("Starting program migration iteration: %d\n", $this->iteration++);

        $v2RootPrograms = $this->read_list_all_root_program_ids( $args );

        if( !$v2RootPrograms ) {
            printf("No user found in iteration %d\n", $this->iteration);
        }

        printf("%s programs found in iteration %d\n", count($v2RootPrograms), $this->iteration);

        $this->migratePrograms($v2RootPrograms);

        $this->offset = $this->offset + $this->limit;
        // if( $this->count >= 20 ) exit;
        if( count($v2RootPrograms) >= $this->limit) {
            $this->migrate( $args );
        }

        // DB::rollback();
        // $this->v2db->rollBack();
        print($this->importedProgramsCount . " programs migrated\n");
        print("Rendering Import Map..\n");
        print_r($this->importMap);
    }

    public function migratePrograms($v2RootPrograms) {
        // DB::beginTransaction();
        // $this->v2db->beginTransaction();
        $importedUsers = [];
        try {
            foreach ($v2RootPrograms as $v2RootProgram) {
                try{
                    $rootProgram = $this->get_program_info ( $v2RootProgram->account_holder_id );
                    $this->setv2pid($v2RootProgram->account_holder_id);
                    // pr($rootProgram);
                    $v2users = $this->migrateUserService->v2_read_list_by_program($v2RootProgram->account_holder_id);
                    $this->migrateUserService->setv2pid($v2RootProgram->account_holder_id);
                    foreach( $v2users as $v2user)   {
                        $importedUsers[] = $this->migrateUserService->migrateSingleUser($v2user);
                    }
                    pr($importedUsers);
                    exit;
                    if( $rootProgram ) {
                        printf("Starting migrations for root program \"%s\"\n", $rootProgram->name);
                        if( !property_exists($rootProgram, "v3_program_id") || !property_exists($rootProgram, "v3_organization_id" ) ) {
                            throw new Exception( "v2Fields \"v3_account_holder_id\" and \"v3_organization_id\" are required in v2 table to sync properly. Termininating!");
                            exit;
                        }
                        $createOrganization = false;

                        if( empty($rootProgram->v3_organization_id) ) {
                            $createOrganization = true;
                        }   else {
                            $exists = Organization::find( $rootProgram->v3_organization_id );
                            if( !$exists ) {
                                $createOrganization = true;
                            }
                        }

                        if( $createOrganization ) {
                            //Create organization
                            try {
                                $organization = Organization::create([
                                    'name' => $rootProgram->name
                                ]);
                                OrganizationCreated::dispatch($organization);
                            } catch (Exception $e) {
                                if( strpos($e->getMessage(), 'Duplicate entry') > 0 && strpos($e->getMessage(), 'organizations_name_unique') > 0) {
                                    $organization = Organization::where([
                                        'name' => $rootProgram->name
                                    ])->first();
                                }
                            }
                            $rootProgram->v3_organization_id = $organization->id;
                        }

                        $skipMigration = false;
                        // pr($organization->toArray());
                        if( empty($rootProgram->v3_program_id) ) {
                            //Let's try to find it in v2
                            $exists = Program::where('v2_account_holder_id', $rootProgram->account_holder_id)
                            ->orWhere('name', 'LIKE', $rootProgram->name)->first();
                            if( $exists )  {
                                $skipMigration = true;
                                printf("\"v3_program_id\" exists for root program \"%s\". Skipping..\n", $rootProgram->name);
                                //Update "v3_program_id" in v2?? Put code here
                            }
                        }   else {
                            if(Program::find($rootProgram->v3_program_id))  {
                                $skipMigration = true;
                            }
                        }

                        if( !$skipMigration ) {
                            try{
                                $newProgram = $this->createProgram($rootProgram->v3_organization_id, $rootProgram);
                                if( $newProgram ) {
                                    printf("Created new v2 program for program \"%s\n", $rootProgram->name);
                                    $this->importedPrograms[] = $newProgram;
                                    $this->importedProgramsCount++;
                                }
                                // pr($this->importedProgramsCount);
                                // pr($newPrograms);
                            } catch(Exception $e)    {
                                throw new Exception( sprintf("Error creating new program. Error:{$e->getMessage()} in Line: {$e->getLine()} in File: {$e->getFile()}", $e->getMessage()));
                            }
                        }
                    }
                } catch(Exception $e)    {
                    throw new Exception("Error fetching v2 program info. Error:{$e->getMessage()} in Line: {$e->getLine()} in File: {$e->getFile()}");
                }
            }
            $this->executeV2SQL();
            $this->executeV3SQL();
            // DB::commit();
            // $this->v2db->commit();
        } catch (Exception $e) {
            // DB::rollback();
            // $this->v2db->rollBack();
            throw new Exception("Error migrating v2 programs into v3. Error:{$e->getMessage()} in Line: {$e->getLine()} in File: {$e->getFile()}");
        }
    }

    public function read_list_all_root_program_ids($arguments = array()) {
        $query = "
        SELECT `" . PROGRAMS . "`.account_holder_id
            FROM `" . PROGRAMS . "`
        WHERE
            ( SELECT
                MAX(COALESCE(`ranking_path_length`.path_length, 0)) as path_length
            FROM
                " . PROGRAM_PATHS . "
                LEFT JOIN " . PROGRAM_PATHS . " AS ranking_path_length ON " . PROGRAM_PATHS . ".descendant = ranking_path_length.descendant and " . PROGRAM_PATHS . ".ancestor != ranking_path_length.ancestor
            WHERE
                " . PROGRAM_PATHS . ".descendant = " . PROGRAMS . ".account_holder_id
            ) = 0";
        if(isset($arguments['label']) && $arguments['label'] != '') {
            $query .= " AND ". PROGRAMS .".label = '" . $arguments['label'] . "'";
        }
        if(isset($arguments['program']) && !empty($arguments['program']) ) {
            $program_account_holder_ids = [];
            if( !is_array($arguments['program']) && ((int) $arguments['program']) > 0 ) {
                $program_account_holder_ids[] = (int) $arguments['program'];
            }   else {
                $program_account_holder_ids = array_filter($arguments['program'], function($p) { return ( (int) $p > 0 ); });
            }
            if( $program_account_holder_ids ) {
                $query .= " AND ". PROGRAMS .".account_holder_id IN (" . implode(',', $program_account_holder_ids) . ")";
            }
        }
        $query .= " LIMIT {$this->offset}, {$this->limit}";

        try{
            return $this->v2db->select($query);
        } catch(\Exception $e) {
            throw new Exception( sprintf("Error fetching v2 programs. Error:%s", $e->getMessage()));
        }
    }
    public function get_program_info( $program_account_holder_id ) {
        if( (int) !$program_account_holder_id ) {
            throw new \InvalidArgumentException ( "Invalid 'program_account_holder_id' passed, should not be empty", 400 );
        }
		$condition = PROGRAMS . ".account_holder_id = {$program_account_holder_id}";

		$query = "
        SELECT
            " . PROGRAMS . ".*
            , " . PROGRAMS_EXTRA . ".uses_units
            , " . PROGRAM_TYPES_TBL . ".type as program_type,
            " . TOKENS . ".id AS token
        FROM
            " . PROGRAMS . "
        LEFT JOIN
            " . PROGRAMS_EXTRA . " ON " . PROGRAMS_EXTRA . ".program_account_holder_id = " . PROGRAMS . ".account_holder_id
        JOIN
            " . PROGRAM_TYPES_TBL . " on " . PROGRAM_TYPES_TBL . ".id = " . PROGRAMS . ".program_type_id
        INNER JOIN
            " . STATE_TYPES_TBL . " ON " . STATE_TYPES_TBL . ".id = " . PROGRAMS . ".program_state_id
        LEFT JOIN
            " . TOKENS . " ON " . TOKENS . ".account_holder_id = " . PROGRAMS . ".account_holder_id
        AND
            " . TOKENS . ".token_type_id = (
                SELECT
                    " . TOKEN_TYPES . ".id
                FROM
                    " . TOKEN_TYPES . "
                WHERE
                    " . TOKEN_TYPES . ".name = '" . TOKEN_TYPE_SIGNUP . "'
            )
        WHERE
            " . $condition . "
            AND
                " . STATE_TYPES_TBL . ".state != '" . PROGRAM_STATE_DELETED . "'
            LIMIT 1";

        try{
            $result = $this->v2db->select($query);
            if( $result ) return current($result);
        } catch(\Exception $e) {
            throw new Exception( sprintf("Error fetching v2 program info. Error:%s", $e->getMessage()));
        }
	}
    public function read_list_config_fields($offset = 0, $limit = 0) {
		// build the query statement to check if we have this program_account_holder_id
		$sql = "
            SELECT
                " . CONFIG_FIELDS . ".*,
                " . CUSTOM_FIELD_TYPES . ".type
            FROM
                " . CONFIG_FIELDS . "
            LEFT JOIN
                " . CUSTOM_FIELD_TYPES . " ON " . CUSTOM_FIELD_TYPES . ".id =  " . CONFIG_FIELDS . ".custom_field_type_id
            ";
		if ($limit > 0) {
			$sql .= " LIMIT {$offset}, {$limit}";
		}
		$result = $this->v2db->select ( $sql );
		if (is_array ( $result ) && count ( $result ) > 0) {
			foreach ( $result as &$row ) {
				$row->rules = $this->read_config_field_rules ( ( int ) $row->id );
				$row->rules_string = '';
				if (is_array ( $row->rules ) && count ( $row->rules ) > 0) {
					$arr_rules = array ();
					foreach ( $row->rules as $rule ) {
						$rule_string = $rule->rule;
						if ($rule->requires_argument) {
							$rule_string .= '[' . $rule->argument . ']';
						}
						$arr_rules [] = $rule_string;
					}
					$row->rules_string = implode ( '|', $arr_rules );
				}
			}
			foreach ( $result as &$row2 ) {
				$field_types = array (
						'id' => 'int',
						'custom_field_type_id' => 'int',
						'require_hierarchy_unique' => 'bool',
						'must_inherit' => 'bool'
				);
				switch ($row2->type) {
					case "int" :
						$field_types ['default_value'] = 'int';
						break;
					case "float" :
						$field_types ['default_value'] = 'float';
						break;
					case "bool" :
						$field_types ['default_value'] = 'bool';
						break;
				}
				$row2 = cast_fieldtypes ( $row2, $field_types );
			}
		}
		return $result;
	}

    public function read_config_field_rules($config_custom_field_id = 0) {
		// build the query statement to check if we have this program_account_holder_id
		$sql = "
            SELECT
                " . CONFIG_FIELDS_HAS_RULES . ".argument,
                " . CONFIG_FIELDS_HAS_RULES . ".config_fields_id,
                " . CONFIG_FIELDS_HAS_RULES . ".custom_fields_rules_id,
                " . CUSTOM_FIELD_RULES . ".rule,
                " . CUSTOM_FIELD_RULES . ".requires_argument
            FROM
                " . CONFIG_FIELDS_HAS_RULES . "
            INNER JOIN
                " . CONFIG_FIELDS . " ON " . CONFIG_FIELDS . ".id = " . CONFIG_FIELDS_HAS_RULES . ".config_fields_id
            INNER JOIN
                " . CUSTOM_FIELD_RULES . " ON " . CUSTOM_FIELD_RULES . ".id = " . CONFIG_FIELDS_HAS_RULES . ".custom_fields_rules_id
            WHERE
                " . CONFIG_FIELDS_HAS_RULES . ".`config_fields_id` = {$config_custom_field_id}";

		$result = $this->v2db->select ( $sql );
		try{
            $result = $this->v2db->select($sql);
            if( $result ) return current($result);
        } catch(\Exception $e) {
            throw new Exception( sprintf("Error fetching v2 read_config_field_rules. Error:%s", $e->getMessage()));
        }
	}

	public function get_top_level_program_id($program_id = 0) {
		// build the query statement to check if we have this program_account_holder_id
		$sql = "SELECT ancestor as program_id" . " FROM " . PROGRAM_PATHS . " WHERE descendant={$program_id}" . " ORDER BY path_length DESC LIMIT 1" . ";";
		// run the query that we built above
		try{
            $result = $this->v2db->select($sql);
            if( $result ) {
                $row = current($result);
                return $row->program_id;
            }   else {
                return $program_id;
            }
        } catch(\Exception $e) {
            throw new Exception( sprintf("Error fetching v2 get_top_level_program_id. Error:%s", $e->getMessage()));
        }
	}

    public function read_extra_program_info($program_account_holder_id = 0) {
		// set query to get extra program info
		$sql = "
			SELECT
			p.*, d.name as default_domain_name
			FROM
				" . PROGRAMS_EXTRA . " AS p
                        LEFT JOIN " . DOMAINS . " AS d ON d.access_key = p.default_domain_access_key
			WHERE
				p.program_account_holder_id = {$program_account_holder_id}
			LIMIT 1";
        try{
            $result = $this->v2db->select($sql);
            if( $result ) {
                $row = current($result);
                $field_types = array (
                    'program_account_holder_id' => 'int',
                    'factor_valuation' => 'int',
                    'points_over_budget' => 'int',
                    'bill_direct' => 'bool',
                    'reserve_percentage' => 'int',
                    'setup_fee' => 'float',
                    'monthly_usage_fee' => 'float',
                    'discount_rebate_percentage' => 'float',
                    'expiration_rebate_percentage' => 'float',
                    'budget_number' => 'float',
                    'alarm_percentage' => 'int',
                    'administrative_fee' => 'float',
                    'administrative_fee_factor' => 'float',
                    'administrative_fee_calculation' => 'string',
                    'fixed_fee' => 'float',
                    'monthly_recurring_points_billing_percentage ' => 'int',
                    'allow_multiple_participants_per_unit' => 'bool',
                    'uses_units' => 'bool',
                    'allow_awarding_pending_activation_participants' => 'bool',
                    'default_domain_name' => 'string',
                    'allow_creditcard_deposits' => 'bool',
                    'air_show_programs_tab' => 'bool',
                    'air_show_manager_award_tab' => 'bool',
                    'air_premium_cost_to_program' => 'bool',
                    'air_show_all_event_list' => 'bool'
                );
                $row = cast_fieldtypes ( $row, $field_types );
                return $row;
            }
        } catch(\Exception $e) {
            throw new Exception( sprintf("Error fetching v2 get_top_level_program_id. Error:%s", $e->getMessage()));
        }
	}
	public function read_program_config_fields_by_name($program_account_holder_id = 0, $config_field_names = array(), $extraArgs=[]) {
		// If no config fields were passed then we want to select all of them.
		// However, for inheritance to work we must populate the config field names list with every config field name
		if (! isset ( $config_field_names ) || ! is_array ( $config_field_names ) || count ( $config_field_names ) == 0) {
			$all_config_fields = $this->read_list_config_fields ( 0, 999999 );
			foreach ( $all_config_fields as $config_field ) {
				$config_field_names [] = $config_field->name;
			}
		}
		$config_field_names = array_unique ( $config_field_names );

		// DAE-31
		$ancestor = 'ancestor';
		$getMoreConfigField = true;
		if(isset($extraArgs['onlySelfDetails']) && $extraArgs['onlySelfDetails'] ==1){
			$ancestor = 'descendant';
			$getMoreConfigField = false;
		}
		$parentProgramId = $this->get_top_level_program_id((int) $program_account_holder_id);

		// Get the config settings for this program and all of its direct ancestors
		$sql = "SELECT
			" . PROGRAMS_CONFIG_FIELDS . ".*,
			if(" . CONFIG_FIELDS . ".access_parent_value = 0 AND " . PROGRAMS_CONFIG_FIELDS . ".program_account_holder_id !={$program_account_holder_id}, " . CONFIG_FIELDS . ".default_value, " . PROGRAMS_CONFIG_FIELDS . ".value) as value,
            " . CONFIG_FIELDS . ".*,
            " . CUSTOM_FIELD_TYPES . ".type,
            GROUP_CONCAT(if(requires_argument, CONCAT(" . CUSTOM_FIELD_RULES . ".rule, '[', argument, ']'), " . CUSTOM_FIELD_RULES . ".rule) SEPARATOR '|') AS rules_string
            FROM
            " . PROGRAM_PATHS . "
            INNER JOIN
            " . PROGRAMS_CONFIG_FIELDS . " ON " . PROGRAMS_CONFIG_FIELDS . ".program_account_holder_id = " . PROGRAM_PATHS . ".{$ancestor}
            INNER JOIN
            " . CONFIG_FIELDS . " ON " . CONFIG_FIELDS . ".id = " . PROGRAMS_CONFIG_FIELDS . ".config_field_id
            INNER JOIN
                " . CUSTOM_FIELD_TYPES . " ON " . CUSTOM_FIELD_TYPES . ".id =  " . CONFIG_FIELDS . ".custom_field_type_id
            LEFT JOIN
                " . CONFIG_FIELDS_HAS_RULES . " ON " . CONFIG_FIELDS_HAS_RULES . ".config_fields_id =  " . CONFIG_FIELDS . ".id
            LEFT JOIN
                " . CUSTOM_FIELD_RULES . " ON " . CUSTOM_FIELD_RULES . ".id = " . CONFIG_FIELDS_HAS_RULES . ".custom_fields_rules_id
            WHERE
				((" . PROGRAM_PATHS . ".descendant = {$program_account_holder_id}  AND  must_inherit != 1) OR (must_inherit = 1 AND " . PROGRAMS_CONFIG_FIELDS . ".program_account_holder_id = {$parentProgramId}))";

		if (isset ( $config_field_names ) && is_array ( $config_field_names ) && count ( $config_field_names ) > 0) {
			// Don't modify the passed in names we will need them later
			$config_field_names_escaped = array ();
			foreach ( $config_field_names as $config_field_name ) {
				$config_field_names_escaped [] = "'{$config_field_name}'";
			}
			$sql .= " AND " . CONFIG_FIELDS . ".`name` IN (" . implode ( ",", $config_field_names_escaped ) . ") ";
		}
		$sql .= "GROUP BY " . PROGRAMS_CONFIG_FIELDS . ".program_account_holder_id, " . CONFIG_FIELDS . ".id ";
		$sql .= "ORDER BY " . CONFIG_FIELDS . ".group, " . PROGRAM_PATHS . ".path_length "; // Order by path length so that the first instance of a config field we encounter is closest to our program in the tree
        try{
            $this->v2db->statement("SET SQL_MODE=''");
            $result = $this->v2db->select($sql);
        } catch(\Exception $e) {
            throw new Exception( sprintf("Error fetching v2 read_config_field_rules. Error:%s", $e->getMessage()));
        }

		// Organize the return data, pluck out the fields that were requested
		$return_data = array ();
		if (isset ( $result ) && is_array ( $result ) && count ( $result ) > 0) {
			foreach ( $result as $row ) {
				// Exit loop if we have everything we came here for
				if (count ( $return_data ) == count ( $config_field_names )) {
					break;
				}
				// If we have already grabbed a config item by this name then skip it
				if (isset ( $return_data [$row->name] )) {
					continue;
				}
				// Set the inherited flag
				if ($row->program_account_holder_id == $program_account_holder_id) {
					$row->inherited = false;
				} else {
					$row->inherited = true;
				}
				$field_types = array (
						'id' => 'int',
						'program_account_holder_id' => 'int',
						'custom_field_type_id' => 'int',
						'require_hierarchy_unique' => 'bool'
				);
				switch ($row->type) {
					case "int" :
						$field_types ['default_value'] = 'int';
						$field_types ['value'] = 'int';
						break;
					case "float" :
						$field_types ['default_value'] = 'float';
						$field_types ['value'] = 'float';
						break;
					case "bool" :
						$field_types ['default_value'] = 'bool';
						$field_types ['value'] = 'bool';
						break;
				}
				$row = cast_fieldtypes ( $row, $field_types );
				$return_data [$row->name] = $row;
			}
		}

		// Determine what rows are missed and grab the defaults
		if (! is_array ( $return_data ) || count ( $return_data ) == 0 || count ( $return_data ) != count ( $config_field_names )) {
			// Collect a list of all of the config fields that we don't have
			$missing_config_fields = array ();
			foreach ( $config_field_names as $config_field_name ) {
				if (! isset ( $return_data [$config_field_name] )) {
					$missing_config_fields [] = $config_field_name;
				}
			}
			// DAE-31
			if($getMoreConfigField == true) {
				// if fields are missing, then use the defaults
				$more_config_fields = $this->read_config_fields_by_name ( $missing_config_fields );
				// Update the inherited flag on all of the fields we just received
				// and use the default value from the field as the value
				if (isset ( $more_config_fields ) && is_array ( $more_config_fields ) && count ( $more_config_fields ) > 0) {
					foreach ( $more_config_fields as $more_config_field ) {
						$more_config_field->inherited = true;
						$more_config_field->value = $more_config_field->default_value;
						$return_data [$more_config_field->name] = $more_config_field;
					}
				}
			}
		}
		ksort ( $return_data );
		// get the row in object type
		return $return_data;
	}

	public function read_config_fields_by_name($config_field_names = array()) {
		$sql = "
            SELECT
            " . CONFIG_FIELDS . ".*,
            " . CUSTOM_FIELD_TYPES . ".type,
            GROUP_CONCAT(if(requires_argument, CONCAT(custom_field_rules.rule, '[', argument, ']'), custom_field_rules.rule) SEPARATOR '|') AS rules_string
            FROM
                " . CONFIG_FIELDS . "
            INNER JOIN
                " . CUSTOM_FIELD_TYPES . " ON " . CUSTOM_FIELD_TYPES . ".id =  " . CONFIG_FIELDS . ".custom_field_type_id
            LEFT JOIN
                " . CONFIG_FIELDS_HAS_RULES . " ON " . CONFIG_FIELDS_HAS_RULES . ".config_fields_id =  " . CONFIG_FIELDS . ".id
            LEFT JOIN
                " . CUSTOM_FIELD_RULES . " ON " . CUSTOM_FIELD_RULES . ".id = " . CONFIG_FIELDS_HAS_RULES . ".custom_fields_rules_id
            WHERE ";
		foreach ( $config_field_names as &$config_field_name ) {
			$config_field_name = "'{$config_field_name}'";
		}
		$sql .= CONFIG_FIELDS . ".`name` IN (" . implode ( ",", $config_field_names ) . ") ";
		$sql .= "GROUP BY " . CONFIG_FIELDS . ".id ";
		$result = $this->v2db->select ( $sql );
		if ( !$result ) {
			throw new \RuntimeException ( 'No result in v2migrate program:read_config_fields_by_name', 500 );
		}
		if (sizeof($result) < 1) {
			throw new \UnexpectedValueException ( 'Unable to find the config field in database record', 500 );
		}
		if (isset ( $result ) && is_array ( $result ) && count ( $result ) > 0) {
			foreach ( $result as &$row ) {
				$field_types = array (
						'id' => 'int',
						'custom_field_type_id' => 'int',
						'require_hierarchy_unique' => 'bool',
						'must_inherit' => 'bool'
				);
				switch ($row->type) {
					case "int" :
						$field_types ['default_value'] = 'int';
						break;
					case "float" :
						$field_types ['default_value'] = 'float';
						break;
					case "bool" :
						$field_types ['default_value'] = 'bool';
						break;
				}
				$row = cast_fieldtypes ( $row, $field_types );
			}
		}
		// get the row in object type
		return $result;
	}

	public function read_list_children_heirarchy($program_id, $direction = 'descendant', $args = array()) {
		// verify $direction if the meets the expected values
		if (! in_array ( $direction, array (
				'descendant',
				'ascendant'
		) )) {
			throw new \UnexpectedValueException ( 'Unexpected value for $direction, must only be ascendant or descendant' );
		}
		// query statement to get the program heirarchy
		// accepts direction whether ascending or descending
		$sql = "CALL sp_closure_table_heirarchy('programs', 'program_paths', {$program_id}, '{$direction}', @result)";
		// execute query to get the table heirarchy
        try{
            $this->v2db->statement("SET SQL_MODE=''");
            $data = $this->v2db->select($sql);
            // this query will return all program's regardless of their state. so we need to filter them again
            $hierarchy_program_account_holder_id = array ();
            if (count ( $data ) > 0) {
                foreach ( $data as $row ) {
                    $hierarchy_program_account_holder_id [] = ( int ) $row->account_holder_id;
                }
            }
            // this will return only the programs that are not deleted
            $programs = $this->read_programs ( $hierarchy_program_account_holder_id, true, 0, 999999999, $args);
            $active_program_account_holder_ids = array ();
            $active_programs = array();
            if (count ( $programs ) > 0) {
                foreach ( $programs as $program ) {
                    $active_program_account_holder_ids [] = $program->account_holder_id;
                    $active_programs[$program->account_holder_id] = $program;
                }
            }
            if (count ( $data ) > 0) {
                for($i = count ( $data ) - 1; $i >= 0; -- $i) {
                    if (! in_array ( $data [$i]->account_holder_id, $active_program_account_holder_ids )) {
                        unset ( $data [$i] );
                    }
                    if (isset($args['get_details']) && $args['get_details']) {
                        $data[$i]->external_id = $active_programs[$data [$i]->account_holder_id]->external_id;
                        $data[$i]->uses_units = $active_programs[$data [$i]->account_holder_id]->uses_units;
                        $data[$i]->label = $active_programs[$data [$i]->account_holder_id]->label;
                    }
                    if (isset($args['get_extra_info']) && $args['get_extra_info']) {
                        $data[$i] = $active_programs[$data [$i]->account_holder_id];
                    }
                }
            }
            $data = array_values ( $data );
            return $data;
        } catch(\Exception $e) {
            throw new Exception( sprintf("Error fetching v2 read_list_heirarchy. Error:%s", $e->getMessage()));
        }
	}

    public function read_programs($program_account_holder_ids, $with_rank = false, $offset = 0, $limit = 0, $extraArgs = array()) {
		$statement = "SELECT
                    " . PROGRAMS . ".*,
                    " . PROGRAMS_EXTRA . ".*
                    , " . PROGRAM_TYPES_TBL . ".type program_type";
		if ($with_rank) {
			$statement .= "
                        , (SELECT
                            GROUP_CONCAT(DISTINCT ranking_program.account_holder_id
                                ORDER BY " . PROGRAM_PATHS . ".path_length DESC) AS rank
                        FROM
                            " . PROGRAM_PATHS . "
                        LEFT JOIN
                            " . PROGRAMS . " AS ranking_program ON " . PROGRAM_PATHS . ".ancestor = ranking_program.account_holder_id
                        WHERE " . PROGRAM_PATHS . ".descendant = " . PROGRAMS . ".account_holder_id
                            ) as rank
                        , ( SELECT
                            MAX(COALESCE(`ranking_path_length`.path_length, 0)) as path_length
                        FROM
                            " . PROGRAM_PATHS . "
                        LEFT JOIN
                            " . PROGRAM_PATHS . " AS ranking_path_length ON " . PROGRAM_PATHS . ".descendant = ranking_path_length.descendant and " . PROGRAM_PATHS . ".ancestor != ranking_path_length.ancestor

                        WHERE " . PROGRAM_PATHS . ".descendant = " . PROGRAMS . ".account_holder_id
                            ) as path_length";
		}
		$statement .= "
                FROM
                    " . PROGRAMS . "
                INNER JOIN " . ACCOUNT_HOLDERS . " ON " . ACCOUNT_HOLDERS . ".id = " . PROGRAMS . ".account_holder_id
                JOIN " . PROGRAM_TYPES_TBL . " on " . PROGRAM_TYPES_TBL . ".id = " . PROGRAMS . ".program_type_id";

		if( isset($extraArgs['exclude_unassigned_domains']) && $extraArgs['exclude_unassigned_domains'] == 1)   {
		    $statement .= " JOIN " . DOMAINS_HAS_PROGRAMS . " ON " . DOMAINS_HAS_PROGRAMS . ".programs_id = " . PROGRAMS . ".account_holder_id";
        } else {
		    $statement .= " LEFT JOIN " . DOMAINS_HAS_PROGRAMS . " ON " . DOMAINS_HAS_PROGRAMS . ".programs_id = " . PROGRAMS . ".account_holder_id";
		}

		$statement .= "
		               LEFT JOIN " . PROGRAMS_EXTRA . " ON " . PROGRAMS_EXTRA . ".program_account_holder_id = " . PROGRAMS . ".account_holder_id
                       INNER JOIN " . STATE_TYPES_TBL . " ON " . STATE_TYPES_TBL . ".id = " . PROGRAMS . ".program_state_id
                       WHERE
                            " . STATE_TYPES_TBL . ".state != '" . PROGRAM_STATE_DELETED . "'";

		if (is_array ( $program_account_holder_ids ) && count ( $program_account_holder_ids ) > 0) {
			$statement .= "
                    AND
                " . PROGRAMS . ".account_holder_id IN (" . implode ( ',', $program_account_holder_ids ) . ")";
		}
		if (isset($extraArgs['program_active']) && $extraArgs['program_active']) {
			$statement .= " AND  " . PROGRAMS . ".`deactivate` = 0 ";
		}
		$statement .= "
                GROUP BY
                    " . PROGRAMS . ".account_holder_id";
		if ($with_rank) {
			$statement .= " ORDER BY rank";
		}
		if ($offset > 0) {
			$statement .= " LIMIT";
			if ($offset > 0) {
				$statement .= $offset . ", ";
			}
			$statement .= $limit;
		}
		try {
			$result = $this->v2db->select ( $statement );
			return $result;
		} catch ( Exception $e ) {
			throw new RuntimeException ( $e, 500 );
		}
	}
}
