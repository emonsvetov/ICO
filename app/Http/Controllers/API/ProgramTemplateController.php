<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Http\Traits\ProgramTemplateMediaUploadTrait;
use App\Http\Requests\ProgramTemplateRequest;
use App\Models\ProgramTemplate;
use Illuminate\Http\Request;
use App\Models\Organization;
use App\Models\Program;

class ProgramTemplateController extends Controller
{
    use ProgramTemplateMediaUploadTrait;

    public function store(ProgramTemplateRequest $request, Organization $organization, Program $program)
    {

        $validated = $request->validated();

        $newProgramTemplate = ProgramTemplate::create( [
            'program_id' => $program->id,
            'welcome_message' => $validated['welcome_message'],
        ] );

        $uploads = $this->handleProgramTemplateMediaUpload( $request, $program );
        
        if( $uploads )   {
            $newProgramTemplate->update( $uploads );
        }
        return response([ 'programTemplate' => $newProgramTemplate ]);
    }

    public function update(ProgramTemplateRequest $request, Organization $organization,  Program $program, ProgramTemplate $programTemplate)
    {
        $validated = $request->validated();
        $fieldsToUpdate = [
            'welcome_message' => $validated['welcome_message']
        ];

        $uploads = $this->handleProgramTemplateMediaUpload( $request, $program );
        
        if( $uploads )   {
            if( isset($uploads['small_logo']) )   {
                $fieldsToUpdate['small_logo'] = $uploads['small_logo'];
            }
            if( isset($uploads['big_logo']) )   {
                $fieldsToUpdate['big_logo'] = $uploads['big_logo'];
            }
        }
        // return  $fieldsToUpdate;
        $programTemplate->update( $fieldsToUpdate );
        return response( $programTemplate );
    }

}
