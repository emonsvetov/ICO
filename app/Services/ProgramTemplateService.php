<?php

namespace App\Services;

use App\Http\Requests\ProgramTemplateRequest;
use App\Http\Traits\ProgramTemplateMediaUploadTrait;
use App\Models\Program;
use App\Models\ProgramTemplate;

class ProgramTemplateService
{
    use ProgramTemplateMediaUploadTrait;

    /**
     * @param ProgramTemplateRequest $request
     * @param Program $program
     * @return ProgramTemplate|null
     */
    public function create(ProgramTemplateRequest $request, Program $program): ?ProgramTemplate
    {
        $newProgramTemplate = ProgramTemplate::create(
            ['program_id' => $program->id] + $request->validated()
        );

        $uploads = $this->handleProgramTemplateMediaUpload($request, $program);

        if ($uploads) {
            $newProgramTemplate->update($uploads);
        }
        
        return $newProgramTemplate;
    }

    /**
     * @param ProgramTemplateRequest $request
     * @param ProgramTemplate $programTemplate
     * @param Program $program
     * @return ProgramTemplate|null
     */
    public function update(ProgramTemplateRequest $request, ProgramTemplate $programTemplate, Program $program): ?ProgramTemplate
    {
        $validated = $request->validated();
        $fieldsToUpdate = [
            'welcome_message' => $validated['welcome_message'],
            'button_color' => $validated['button_color'],
            'button_bg_color' => $validated['button_bg_color'],
            'button_corner' => $validated['button_corner'],
        ];

        $uploads = $this->handleProgramTemplateMediaUpload($request, $program);

        if ($uploads) {
            foreach ($uploads as $key => $upload) {
                if (in_array($key, ProgramTemplate::IMAGE_FIELDS)) {
                    $fieldsToUpdate[$key] = $upload;
                }
            }
        }
        $programTemplate->update($fieldsToUpdate);

        return $programTemplate;
    }


}
