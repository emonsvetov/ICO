<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use App\Models\User;
use App\Services\ProgramService;
use DB;

class EmailTemplateService
{
    private ProgramService $programService;
    public function __construct(
        ProgramService $programService
    ) {
        $this->programService = $programService;
    }

    public function read_list_program_email_templates_by_type($program_account_holder_id = 0, $type = '', $offset = 0, $limit = 0) {
        //pr('there');
        //die;
		$params=['program_account_holder_id'=>$program_account_holder_id, 'type'=>$type,'offset'=>$offset,'limit'=>$limit];
		// build the query statement to check if we have this program_account_holder_id
		$sql = "
		SELECT programs_email_templates.*, email_template_types.type FROM programs_email_templates LEFT JOIN email_template_types ON email_template_types.id =  programs_email_templates.email_template_type_id WHERE programs_email_templates.program_account_holder_id = :program_account_holder_id AND email_template_types.type = :type LIMIT :offset, :limit";
        try {
            $result = DB::select( DB::raw($sql), $params);
        } catch (Exception $e) {
            throw new Exception ( 'Could not get email templates. DB query failed with error:' . $e->getMessage(), 400 );
        }
        $program = $this->programService->get_parent_program_by_account( $program_account_holder_id);
       
       // $program->load('parent');
       // pr($program); die;
		//pr($this->programService->is_sub_program( $program_account_holder_id )); die;
		if (sizeof ( $result ) == 0) {
			// scan up the heirarchy to see if we can find a match for this email template.
            $parent_program = $this->programService->get_parent_program_by_account( $program_account_holder_id);
            
			if (!empty($parent_program->account_holder_id)) {
				return $this->read_list_program_email_templates_by_type ( $parent_program->account_holder_id, $type, $offset, $limit );
			}
		}
		return $result;

	}
}
