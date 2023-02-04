<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use App\Models\User;
use App\Models\ProgramEmailTemplate;
use App\Services\ProgramService;
use DB;

class ProgramEmailTemplateService
{
    private ProgramService $programService;
    public function __construct(
        ProgramService $programService
    ) {
        $this->programService = $programService;
    }
      /**
     * @param array $read_list_program_email_templates_by_type
     * @return array
     */
    public function read_list_program_email_templates_by_type($program, $type = '') {
        $offset = 0; $limit = 99999;
		$params=['program_id'=>$program->id, 'type'=>$type,'offset'=>$offset,'limit'=>$limit];
		// build the query statement to check if we have this program_account_holder_id
		$sql = "
		SELECT programs_email_templates.*, email_template_types.type FROM programs_email_templates LEFT JOIN email_template_types ON email_template_types.id =  programs_email_templates.email_template_type_id WHERE programs_email_templates.program_id = :program_id AND email_template_types.type = :type LIMIT :offset, :limit";
        try {
            $result = DB::select( DB::raw($sql), $params);
        } catch (Exception $e) {
            throw new Exception ( 'Could not get email templates. DB query failed with error:' . $e->getMessage(), 400 );
        }
        //pr($result); die;
		if (sizeof ( $result ) == 0) {
			// scan up the heirarchy to see if we can find a match for this email template.
            $parent_program = $program->parent;
            if (!empty($program->parent)) {
                return $this->read_list_program_email_templates_by_type( $program->parent, $type);
            } else {
                return false;
            }
		}
		return $result;
	}
}
