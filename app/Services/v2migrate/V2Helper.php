<?php

namespace App\Services\v2migrate;

use Illuminate\Database\ConnectionInterface;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Exception;

class V2Helper
{
    public ConnectionInterface $v2db;
    public int $offset = 0;
    public int $limit = 9999;

    protected function __construct()
    {
        $this->v2db = DB::connection('v2');
    }


    public function read_list_all_root_program_ids($arguments = [])
    {
        $query = "
            SELECT
                `" . PROGRAMS . "`.account_holder_id,
                `" . PROGRAMS . "`.name,
                `" . PROGRAMS . "`.v3_program_id
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
        if (isset($arguments['label']) && $arguments['label'] != '') {
            $query .= " AND " . PROGRAMS . ".label = '" . $arguments['label'] . "'";
        }
        if (isset($arguments['name']) && $arguments['name'] != '') {
            $query .= " AND " . PROGRAMS . ".name LIKE '%" . $arguments['name'] . "%'";
        }
        if (isset($arguments['program']) && !empty($arguments['program'])) {
            $program_account_holder_ids = [];
            if (!is_array($arguments['program']) && ((int)$arguments['program']) > 0) {
                $program_account_holder_ids[] = (int)$arguments['program'];
            } else {
                $program_account_holder_ids = array_filter($arguments['program'], function ($p) {
                    return ((int)$p > 0);
                });
            }
            if ($program_account_holder_ids) {
                $query .= " AND " . PROGRAMS . ".account_holder_id IN (" . implode(',', $program_account_holder_ids) . ")";
            }
        }
        $query .= " LIMIT {$this->offset}, {$this->limit}";

        try {
            return $this->v2db->select($query);
        } catch (\Exception $e) {
            throw new Exception(sprintf("Error fetching v2 programs. Error:%s", $e->getMessage()));
        }
    }

    public function get_program_info(int $program_account_holder_id)
    {
        if (!$program_account_holder_id) {
            throw new \InvalidArgumentException ("Invalid 'program_account_holder_id' passed, should not be empty", 400);
        }
        $condition = PROGRAMS . ".account_holder_id = {$program_account_holder_id}";

        $query = "
        SELECT
            " . PROGRAMS . ".*
            , " . PROGRAMS_EXTRA . ".uses_units
            , " . PROGRAM_TYPES_TBL . ".type as program_type
            , " . PROGRAMS_EXTRA . ".bill_direct as bill_direct
            , " . TOKENS . ".id AS token
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

        try {
            $result = $this->v2db->select($query);
            if ($result) return current($result);
        } catch (\Exception $e) {
            throw new Exception(sprintf("Error fetching v2 program info. Error:%s", $e->getMessage()));
        }
    }

    public function read_list_config_fields($offset = 0, $limit = 0)
    {
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
        $result = $this->v2db->select($sql);
        if (is_array($result) && count($result) > 0) {
            foreach ($result as &$row) {
                $row->rules = $this->read_config_field_rules(( int )$row->id);
                $row->rules_string = '';
                if (is_array($row->rules) && count($row->rules) > 0) {
                    $arr_rules = array();
                    foreach ($row->rules as $rule) {
                        $rule_string = $rule->rule;
                        if ($rule->requires_argument) {
                            $rule_string .= '[' . $rule->argument . ']';
                        }
                        $arr_rules [] = $rule_string;
                    }
                    $row->rules_string = implode('|', $arr_rules);
                }
            }
            foreach ($result as &$row2) {
                $field_types = array(
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
                $row2 = cast_fieldtypes($row2, $field_types);
            }
        }
        return $result;
    }

    public function read_config_field_rules($config_custom_field_id = 0)
    {
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

        $result = $this->v2db->select($sql);
        try {
            $result = $this->v2db->select($sql);
            if ($result) return current($result);
        } catch (\Exception $e) {
            throw new Exception(sprintf("Error fetching v2 read_config_field_rules. Error:%s", $e->getMessage()));
        }
    }

    public function get_top_level_program_id($program_id = 0)
    {
        // build the query statement to check if we have this program_account_holder_id
        $sql = "SELECT ancestor as program_id" . " FROM " . PROGRAM_PATHS . " WHERE descendant={$program_id}" . " ORDER BY path_length DESC LIMIT 1" . ";";
        // run the query that we built above
        try {
            $result = $this->v2db->select($sql);
            if ($result) {
                $row = current($result);
                return $row->program_id;
            } else {
                return $program_id;
            }
        } catch (\Exception $e) {
            throw new Exception(sprintf("Error fetching v2 get_top_level_program_id. Error:%s", $e->getMessage()));
        }
    }

    public function read_extra_program_info($program_account_holder_id = 0)
    {
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
        try {
            $result = $this->v2db->select($sql);
            if ($result) {
                $row = current($result);
                $field_types = array(
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
                $row = cast_fieldtypes($row, $field_types);
                return $row;
            }
        } catch (\Exception $e) {
            throw new Exception(sprintf("Error fetching v2 get_top_level_program_id. Error:%s", $e->getMessage()));
        }
    }

    public function read_program_config_fields_by_name($program_account_holder_id = 0, $config_field_names = array(), $extraArgs = [])
    {
        // If no config fields were passed then we want to select all of them.
        // However, for inheritance to work we must populate the config field names list with every config field name
        if (!isset ($config_field_names) || !is_array($config_field_names) || count($config_field_names) == 0) {
            $all_config_fields = $this->read_list_config_fields(0, 999999);
            foreach ($all_config_fields as $config_field) {
                $config_field_names [] = $config_field->name;
            }
        }
        $config_field_names = array_unique($config_field_names);

        // DAE-31
        $ancestor = 'ancestor';
        $getMoreConfigField = true;
        if (isset($extraArgs['onlySelfDetails']) && $extraArgs['onlySelfDetails'] == 1) {
            $ancestor = 'descendant';
            $getMoreConfigField = false;
        }
        $parentProgramId = $this->get_top_level_program_id((int)$program_account_holder_id);

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

        if (isset ($config_field_names) && is_array($config_field_names) && count($config_field_names) > 0) {
            // Don't modify the passed in names we will need them later
            $config_field_names_escaped = array();
            foreach ($config_field_names as $config_field_name) {
                $config_field_names_escaped [] = "'{$config_field_name}'";
            }
            $sql .= " AND " . CONFIG_FIELDS . ".`name` IN (" . implode(",", $config_field_names_escaped) . ") ";
        }
        $sql .= "GROUP BY " . PROGRAMS_CONFIG_FIELDS . ".program_account_holder_id, " . CONFIG_FIELDS . ".id ";
        $sql .= "ORDER BY " . CONFIG_FIELDS . ".group, " . PROGRAM_PATHS . ".path_length "; // Order by path length so that the first instance of a config field we encounter is closest to our program in the tree
        try {
            $this->v2db->statement("SET SQL_MODE=''");
            $result = $this->v2db->select($sql);
        } catch (\Exception $e) {
            throw new Exception(sprintf("Error fetching v2 read_config_field_rules. Error:%s", $e->getMessage()));
        }

        // Organize the return data, pluck out the fields that were requested
        $return_data = array();
        if (isset ($result) && is_array($result) && count($result) > 0) {
            foreach ($result as $row) {
                // Exit loop if we have everything we came here for
                if (count($return_data) == count($config_field_names)) {
                    break;
                }
                // If we have already grabbed a config item by this name then skip it
                if (isset ($return_data [$row->name])) {
                    continue;
                }
                // Set the inherited flag
                if ($row->program_account_holder_id == $program_account_holder_id) {
                    $row->inherited = false;
                } else {
                    $row->inherited = true;
                }
                $field_types = array(
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
                $row = cast_fieldtypes($row, $field_types);
                $return_data [$row->name] = $row;
            }
        }

        // Determine what rows are missed and grab the defaults
        if (!is_array($return_data) || count($return_data) == 0 || count($return_data) != count($config_field_names)) {
            // Collect a list of all of the config fields that we don't have
            $missing_config_fields = array();
            foreach ($config_field_names as $config_field_name) {
                if (!isset ($return_data [$config_field_name])) {
                    $missing_config_fields [] = $config_field_name;
                }
            }
            // DAE-31
            if ($getMoreConfigField == true) {
                // if fields are missing, then use the defaults
                $more_config_fields = $this->read_config_fields_by_name($missing_config_fields);
                // Update the inherited flag on all of the fields we just received
                // and use the default value from the field as the value
                if (isset ($more_config_fields) && is_array($more_config_fields) && count($more_config_fields) > 0) {
                    foreach ($more_config_fields as $more_config_field) {
                        $more_config_field->inherited = true;
                        $more_config_field->value = $more_config_field->default_value;
                        $return_data [$more_config_field->name] = $more_config_field;
                    }
                }
            }
        }
        ksort($return_data);
        // get the row in object type
        return $return_data;
    }

    public function read_config_fields_by_name($config_field_names = array())
    {
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
        foreach ($config_field_names as &$config_field_name) {
            $config_field_name = "'{$config_field_name}'";
        }
        $sql .= CONFIG_FIELDS . ".`name` IN (" . implode(",", $config_field_names) . ") ";
        $sql .= "GROUP BY " . CONFIG_FIELDS . ".id ";
        $result = $this->v2db->select($sql);
        if (!$result) {
            throw new \RuntimeException ('No result in v2migrate program:read_config_fields_by_name', 500);
        }
        if (sizeof($result) < 1) {
            throw new \UnexpectedValueException ('Unable to find the config field in database record', 500);
        }
        if (isset ($result) && is_array($result) && count($result) > 0) {
            foreach ($result as &$row) {
                $field_types = array(
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
                $row = cast_fieldtypes($row, $field_types);
            }
        }
        // get the row in object type
        return $result;
    }

    public function read_list_children_heirarchy($program_id, $direction = 'descendant', $args = array())
    {
        // verify $direction if the meets the expected values
        if (!in_array($direction, array(
            'descendant',
            'ascendant'
        ))) {
            throw new \UnexpectedValueException ('Unexpected value for $direction, must only be ascendant or descendant');
        }
        // query statement to get the program heirarchy
        // accepts direction whether ascending or descending
        $sql = "CALL sp_closure_table_heirarchy('programs', 'program_paths', {$program_id}, '{$direction}', @result)";
        // execute query to get the table heirarchy
        try {
            $this->v2db->statement("SET SQL_MODE=''");
            $data = $this->v2db->select($sql);
            // this query will return all program's regardless of their state. so we need to filter them again
            $hierarchy_program_account_holder_id = array();
            if (count($data) > 0) {
                foreach ($data as $row) {
                    $hierarchy_program_account_holder_id [] = ( int )$row->account_holder_id;
                }
            }
            // this will return only the programs that are not deleted
            $programs = $this->read_programs($hierarchy_program_account_holder_id, true, 0, 999999999, $args);
            $active_program_account_holder_ids = array();

            $active_programs = array();
            if (count($programs) > 0) {
                foreach ($programs as $program) {
                    $active_program_account_holder_ids [] = $program->account_holder_id;
                    $active_programs[$program->account_holder_id] = $program;
                }
            }

            if (count($data) > 0) {
                for ($i = count($data) - 1; $i >= 0; --$i) {
                    if (!in_array($data [$i]->account_holder_id, $active_program_account_holder_ids)) {
                        unset ($data [$i]);
                    }
                    if (isset($args['get_details']) && $args['get_details']) {
                        $data[$i]->external_id = $active_programs[$data [$i]->account_holder_id]->external_id;
                        $data[$i]->uses_units = $active_programs[$data [$i]->account_holder_id]->uses_units;
                        $data[$i]->label = $active_programs[$data [$i]->account_holder_id]->label;
                    }
                    $data[$i]->v3_organization_id = $active_programs[$data [$i]->account_holder_id]->v3_organization_id;
                    $data[$i]->v3_program_id = $active_programs[$data [$i]->account_holder_id]->v3_program_id;
                    if (isset($args['get_extra_info']) && $args['get_extra_info']) {
                        $data[$i] = $active_programs[$data [$i]->account_holder_id];
                    }
                }
            }
            $data = array_values($data);
            return $data;
        } catch (\Exception $e) {
            throw new Exception(sprintf("Error fetching v2 read_list_heirarchy. Error:%s", $e->getMessage()));
        }
    }

    public function read_programs($program_account_holder_ids, $with_rank = false, $offset = 0, $limit = 0, $extraArgs = array())
    {
        $statement = "SELECT
                    " . PROGRAMS . ".*,
                    " . PROGRAMS_EXTRA . ".*
                    , " . PROGRAM_TYPES_TBL . ".type program_type";
        if ($with_rank) {
            $statement .= "
                        , (SELECT
                            GROUP_CONCAT(DISTINCT ranking_program.account_holder_id
                                ORDER BY " . PROGRAM_PATHS . ".path_length DESC) AS 'rank'
                        FROM
                            " . PROGRAM_PATHS . "
                        LEFT JOIN
                            " . PROGRAMS . " AS ranking_program ON " . PROGRAM_PATHS . ".ancestor = ranking_program.account_holder_id
                        WHERE " . PROGRAM_PATHS . ".descendant = " . PROGRAMS . ".account_holder_id
                            ) as 'rank'
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

        if (isset($extraArgs['exclude_unassigned_domains']) && $extraArgs['exclude_unassigned_domains'] == 1) {
            $statement .= " JOIN " . DOMAINS_HAS_PROGRAMS . " ON " . DOMAINS_HAS_PROGRAMS . ".programs_id = " . PROGRAMS . ".account_holder_id";
        } else {
            $statement .= " LEFT JOIN " . DOMAINS_HAS_PROGRAMS . " ON " . DOMAINS_HAS_PROGRAMS . ".programs_id = " . PROGRAMS . ".account_holder_id";
        }

        $statement .= "
		               LEFT JOIN " . PROGRAMS_EXTRA . " ON " . PROGRAMS_EXTRA . ".program_account_holder_id = " . PROGRAMS . ".account_holder_id
                       INNER JOIN " . STATE_TYPES_TBL . " ON " . STATE_TYPES_TBL . ".id = " . PROGRAMS . ".program_state_id
                       WHERE
                            " . STATE_TYPES_TBL . ".state != '" . PROGRAM_STATE_DELETED . "'";

        if (is_array($program_account_holder_ids) && count($program_account_holder_ids) > 0) {
            $statement .= "
                    AND
                " . PROGRAMS . ".account_holder_id IN (" . implode(',', $program_account_holder_ids) . ")";
        }
        if (isset($extraArgs['program_active']) && $extraArgs['program_active']) {
            $statement .= " AND  " . PROGRAMS . ".`deactivate` = 0 ";
        }
        $statement .= "
                GROUP BY
                    " . PROGRAMS . ".account_holder_id";
        if ($with_rank) {
            $statement .= " ORDER BY 'rank'";
        }
        if ($offset > 0) {
            $statement .= " LIMIT";
            if ($offset > 0) {
                $statement .= $offset . ", ";
            }
            $statement .= $limit;
        }
        try {
            $result = $this->v2db->select($statement);
            return $result;
        } catch (Exception $e) {
            throw new RuntimeException ($e, 500);
        }
    }

    public function v2_read_list_by_program($v2_program_account_holder_id = 0, $role_types = [], $args = [])
    {
        return $this->_get_users_with_roles($v2_program_account_holder_id, $role_types, $args);
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
    private function _get_users_with_roles($program_account_holder_id = 0, $role_types = array(), $args = [])
    {
        $hierarchy = false;
        $role_type_count = count($role_types);
        $role_types_string = '';
        if (( int )$role_type_count > 0) {
            for ($x = 0; $x < $role_type_count; $x++) {
                $role_types [$x] = "'" . $role_types [$x] . "'";
            }
            $role_types_string = implode(', ', $role_types);
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

        $sql = $sql . " " . $this->_where_in_program($hierarchy, $program_account_holder_id);

        if (isset($args['user_state_id']) && !empty($args['user_state_id'])) {
            $user_state_ids = is_array($args['user_state_id']) ? implode(',', $args['user_state_id']) : $args['user_state_id'];
            $sql = $sql . " AND users.user_state_id IN ({$user_state_ids}) ";
        }

        if (isset($args['active']) && $args['active'] == true) {
            $sql = $sql . " AND users.user_state_id = 2 ";
        }

        if ($role_types_string != "") {
            $sql .= " AND role_types.type IN ({$role_types_string})";
        }

        $sql = $sql . "
			GROUP BY
				users.account_holder_id
		;";

        if ($this->isPrintSql()) {
            $this->printf("SQL: %s\n", $sql);
        }

        return $this->v2db->select($sql);
    }

    private function _where_in_program($hierarchy = false, $program_id = 0, $all = false)
    {
        $sql = "";
        if ($hierarchy) {
            if (!$all) {
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
            $root_program_id = resolve(\App\Services\v2migrate\MigrateProgramsService::class)->get_top_level_program_id($program_id);
            $sql = "
				program_paths.ancestor in (select descendant from program_paths where ancestor = {$root_program_id})
			";
        }
        return $sql;
    }

    public function getUsersLog($v2AccountHolderId): array
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
        return $this->v2db->select($sql);
    }

}