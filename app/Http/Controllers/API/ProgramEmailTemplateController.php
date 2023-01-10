<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use Illuminate\Http\Request;
use App\Models\Program;
use App\Services\ProgramEmailTemplateService;
use App\Models\User;
use App\Models\Organization;

class ProgramEmailTemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Organization $organization, Program $program, ProgramEmailTemplateService $programEmailTemplateService)
    {
       $type = request()->get('type');
       $programEmailTemplates = $programEmailTemplateService->read_list_program_email_templates_by_type($program, $type);
       if (!empty($programEmailTemplates) && sizeof($programEmailTemplates) > 0 )
       {
           return response( $programEmailTemplates );
       }
       return response( [] );
    }
}
