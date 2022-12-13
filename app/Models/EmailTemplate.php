<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class EmailTemplate extends Model
{
    use HasFactory;
    protected $guarded = [];
    /*/git-clean/core-program/php_includes/application/models/email_templates_model.php*/
    /*	public function read_list_program_email_templates_by_type($program_account_holder_id = 0, $type = '', $offset = 0, $limit = 0) {
		// check if we have a valid $program_account_holder_id format
		if (! is_int ( $program_account_holder_id ) || $program_account_holder_id < 1) {
			throw new InvalidArgumentException ( 'Invalid "program_account_holder_id" passed, must be an integer and greater than 0', 400 );
		}
		if (! is_string ( $type ) || strlen ( trim ( $type ) ) < 1) {
			throw new InvalidArgumentException ( 'Invalid "type" passed, must be an string with a trimmed length > 0', 400 );
		}
		// check if we have a valid $program_account_holder_id format
		if (! is_int ( $offset ) || $offset < 0) {
			throw new InvalidArgumentException ( 'Invalid "offset" passed, must be an integer and greater than 0', 400 );
		}
		// check if we have a valid $program_account_holder_id format
		if (! is_int ( $limit ) || $limit < 1) {
			throw new InvalidArgumentException ( 'Invalid "limit" passed, must be an integer and greater than 0', 400 );
		}
		try {
			return $this->client->ReadListProgramEmailTemplatesByType ( $program_account_holder_id, $type, $offset, $limit );
		} catch ( Exception $e ) {
			throw new RuntimeException ( $e->getMessage (), 400 );
		}
	
	}
    /git-clean/core-api/php_includes/application/controllers/email_templates.php
    public function ReadListProgramEmailTemplatesByType($program_account_holder_id = 0, $type = '', $offset = 0, $limit = 0) {
		if ($this->_can_execute_in_program ( $program_account_holder_id )) {
			return $this->programs_email_templates_model->read_list_program_email_templates_by_type ( $program_account_holder_id, $type, $offset, $limit );
		}
	
	}
    /git-clean/core-api/php_includes/application/models/programs_email_templates_model.php
     // list_all_ids_of_type
    list_all_ids_of_type
	public function read_list_program_email_templates_by_type($program_account_holder_id = 0, $type = '', $offset = 0, $limit = 0) {
		assert_is_positive_int ( "program_account_holder_id", $program_account_holder_id );
		assert_is_not_empty_string ( "type", $type );
		assert_is_positive_int_or_zero ( "offset", $offset );
		assert_is_positive_int ( "limit", $limit );
		// build the query statement to check if we have this program_account_holder_id
		$sql = "
            SELECT
                " . PROGRAMS_EMAIL_TEMPLATES . ".*,
                " . EMAIL_TEMPLATE_TYPES . ".type
            FROM
                " . PROGRAMS_EMAIL_TEMPLATES . "
            LEFT JOIN
                " . EMAIL_TEMPLATE_TYPES . " ON " . EMAIL_TEMPLATE_TYPES . ".id =  " . PROGRAMS_EMAIL_TEMPLATES . ".email_template_type_id
            WHERE
                " . PROGRAMS_EMAIL_TEMPLATES . ".`program_account_holder_id` = {$this->read_db->escape($program_account_holder_id)} 
            AND 
                " . EMAIL_TEMPLATE_TYPES . ".type = {$this->read_db->escape($type)} 
            LIMIT {$offset}, {$limit}";
		$query = $this->read_db->query ( $sql );
		if (! $query) {
			throw new RuntimeException ( 'Internal query failed, please contact the API administrator', 500 );
		}
		$result = $query->result ();
		if (sizeof ( $result ) == 0) {
			// scan up the heirarchy to see if we can find a match for this email template.
			if ($this->programs_model->is_sub_program ( $program_account_holder_id )) {
				$parent_id = $this->programs_model->read_parent_account_holder_id ( $program_account_holder_id );
				return $this->read_list_program_email_templates_by_type ( ( int ) $parent_id, $type, $offset, $limit );
			}
		}
		return $result;

	}
    /git-clean/core-api/php_includes/application/core/APP_Base_Class.php
    protected function _can_execute_in_programs($program_account_holder_ids = array()) {
		return true;
		if (is_array ( $program_account_holder_ids ) && count ( $program_account_holder_ids ) > 0) {
			// Make sure only integers were passed in the program ids array
			foreach ( $program_account_holder_ids as $program_account_holder_id ) {
				if (! is_int ( $program_account_holder_id ) || $program_account_holder_id < 1) {
					throw new InvalidArgumentException ( 'Invalid "program_account_holder_ids" passed, must be an array of integers that are > 0', 400 );
				}
				if (! $this->_can_execute_in_program ( $program_account_holder_id )) {
					return false;
				}
			}
			return true;
		} else {
			throw new InvalidArgumentException ( 'Invalid "program_account_holder_ids" passed, must be an array of integers that are > 0', 400 );
		}
	
	}

	}
    */
    public static function read_list_program_email_templates_by_type($program_account_holder_id = 0, $type = '', $offset = 0, $limit = 0) {
		$params=['program_account_holder_id'=>$program_account_holder_id, 'type'=>$type,'offset'=>$offset,'limit'=>$limit];
		// build the query statement to check if we have this program_account_holder_id
		$sql = "
		SELECT programs_email_templates.*, email_template_types.type FROM programs_email_templates LEFT JOIN email_template_types ON email_template_types.id =  programs_email_templates.email_template_type_id WHERE programs_email_templates.program_account_holder_id = :program_account_holder_id AND email_template_types.type = :type LIMIT :offset, :limit";
        try {
            $result = DB::select( DB::raw($sql), $params);
        } catch (Exception $e) {
            throw new Exception ( 'Could not get email templates. DB query failed with error:' . $e->getMessage(), 400 );
        }
		//$this->programs_model->is_sub_program ( $program_account_holder_id )
		if (sizeof ( $result ) == 0) {
			// scan up the heirarchy to see if we can find a match for this email template.
			if ($this->programs_model->is_sub_program ( $program_account_holder_id )) {
				$parent_id = $this->programs_model->read_parent_account_holder_id ( $program_account_holder_id );
				return $this->read_list_program_email_templates_by_type ( ( int ) $parent_id, $type, $offset, $limit );
			}
		}
		return $result;

	}
}
