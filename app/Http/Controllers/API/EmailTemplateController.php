<?php

namespace App\Http\Controllers\API;

use App\Http\Requests\EmailTemplateRequest;
use App\Services\EmailTemplateService;
use App\Http\Controllers\Controller;
use App\Models\EmailTemplate;
use App\Models\EmailTemplateType;
use App\Models\Program;
use App\Models\Organization;

class EmailTemplateController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Organization $organization, Program $program)
    {
        //
        $where = [
            'organization_id' => $organization->id,
            'program_id' => $program->id,
        ];
        $type = request()->get('type');
        if( $type )
        {
            $where['type'] = $type;
        }
        $emailTemplates = EmailTemplate::where($where)->get();

        if ( $emailTemplates->isNotEmpty() )
        {
            return response( $emailTemplates );
        }
        return response( [] );
    }

    public function update(EmailTemplateRequest $request, Organization $organization, Program $program, EmailTemplate $emailTemplate, EmailTemplateService $emailTemplateService )
    {
        $validated = $request->validated();
        try {
            return response(['emailTemplate' => $emailTemplateService->update($emailTemplate, $validated)]);
        }
        catch(\Throwable $e)
        {
            return response(['errors' => 'Error updating email template', 'e' => sprintf('Error %s in line  %d', $e->getMessage(), $e->getLine())], 422);
        }
    }

    /**
     * Get a listing of email template types.
     *
     * @return \Illuminate\Http\Response
     */
    public function typeList(Organization $organization, Program $program)
    {
        $emailTemplates = EmailTemplateType::get();
        if ( $emailTemplates->isNotEmpty() )
        {
            return response( $emailTemplates );
        }
        return response( [] );
    }
}
