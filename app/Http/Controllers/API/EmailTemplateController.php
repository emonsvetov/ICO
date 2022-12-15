<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use Illuminate\Http\Request;
use App\Models\Program;
use App\Services\EmailTemplateService;

class EmailTemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
        $type = request()->get('type');
        $emailTemplates = EmailTemplate::where('type', $type)
                        ->get();

        if ( $emailTemplates->isNotEmpty() )
        {
            return response( $emailTemplates );
        }
        return response( [] );
    }

    /*public function program_email_templates(Program $program, $type = "Goal Progress", EmailTemplateService $emailTemplateService) {
       // pr($program->account_holder_id); die;
        $emailTemplates = $emailTemplateService->read_list_program_email_templates_by_type($program->account_holder_id, $type, 0, 9999);
        if ( sizeof($emailTemplates) > 0 )
        {
            return response( $emailTemplates );
        }
        return response( [] );
    }*/

}
