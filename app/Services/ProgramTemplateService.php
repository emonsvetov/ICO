<?php

namespace App\Services;

use App\Http\Requests\ProgramTemplateRequest;
use App\Http\Traits\ProgramTemplateMediaUploadTrait;
use App\Models\Program;
use App\Models\ProgramTemplate;
use Illuminate\Support\Facades\Storage;

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
        $validated = $request->validated();
        $fieldsToCreate = [
            'welcome_message' => isset($validated['welcome_message']) && $validated['welcome_message'] == 'null' ? null : $validated['welcome_message'] ?? null,'participant_homepage_message' => isset($validated['participant_homepage_message']) && $validated['participant_homepage_message'] == 'null' ? null : $validated['participant_homepage_message'] ?? null,
            'theme_color' => isset($validated['theme_color']) &&  $validated['theme_color'] == 'null' ? null : $validated['theme_color'] ?? null,
            'button_color' => isset($validated['button_color']) &&  $validated['button_color'] == 'null' ? null : $validated['button_color'] ?? null,
            'button_bg_color' => isset($validated['button_bg_color']) &&  $validated['button_bg_color'] == 'null' ? null : $validated['button_bg_color'] ?? null,
            'button_corner' => $validated['button_corner'],
            'font_family' => isset($validated['font_family']) &&  $validated['font_family'] == 'null' ? null : $validated['font_family'] ?? null,
            'name' => $validated['name'],
        ];
        $newProgramTemplate = ProgramTemplate::create(
            ['program_id' => $program->id, 'updated_by' => auth()->user()->id] + $fieldsToCreate
        );

        $uploads = $this->handleProgramTemplateMediaUpload($request, $program, $newProgramTemplate);

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
            'welcome_message' => isset($validated['welcome_message']) && $validated['welcome_message'] == 'null' ? null : $validated['welcome_message'] ?? null,'participant_homepage_message' => isset($validated['participant_homepage_message']) && $validated['participant_homepage_message'] == 'null' ? null : $validated['participant_homepage_message'] ?? null,
            'theme_color' => isset($validated['theme_color']) &&  $validated['theme_color'] == 'null' ? null : $validated['theme_color'] ?? null,
            'button_color' => isset($validated['button_color']) &&  $validated['button_color'] == 'null' ? null : $validated['button_color'] ?? null,
            'button_bg_color' => isset($validated['button_bg_color']) &&  $validated['button_bg_color'] == 'null' ? null : $validated['button_bg_color'] ?? null,
            'button_corner' => $validated['button_corner'],
            'font_family' => isset($validated['font_family']) &&  $validated['font_family'] == 'null' ? null : $validated['font_family'] ?? null,
            'name' => $validated['name'],
        ];

        $uploads = $this->handleProgramTemplateMediaUpload($request, $program, $programTemplate, true);

        if ($uploads) {
            foreach ($uploads as $key => $upload) {
                if (in_array($key, ProgramTemplate::IMAGE_FIELDS)) {
                    $fieldsToUpdate[$key] = $upload;
                }
            }
        }

        $fieldsToUpdate['updated_at'] = now();
        $fieldsToUpdate['updated_by'] = auth()->user()->id;
        $fieldsToUpdate['is_active'] = true;

        $programTemplate->update($fieldsToUpdate);
        $programTemplate->where('id', '!=', $programTemplate->id)
        ->where('program_id', '=', $programTemplate->program_id)
        ->update(['is_active'=>false]);
        return $programTemplate;
    }
    /**
     * @param ProgramTemplate $programTemplate
     * @return bool
     */
    public function deleteTemplate(ProgramTemplate $programTemplate): bool
    {
        foreach($programTemplate->getAttributes() as $key=> $item){
            if (in_array($key, ProgramTemplate::IMAGE_FIELDS)) {
                $oldFile = $programTemplate[$key];
                if( $oldFile && strpos('theme/default', $oldFile) === false )  {
                    Storage::delete( $oldFile );
                }
            }
        }
        return $programTemplate->delete();
    }
    function getTemplateByName(Program $program, $name)
    {
        $programTemplate = $program->templates()->where('name', 'LIKE', $name)->first();
        return $programTemplate;
    }

    private function patchImageFieldsWithDefault($programTemplate)
    {
        foreach($programTemplate->getAttributes() as $key=> $item){
            if (in_array($key, ProgramTemplate::IMAGE_FIELDS)) {
                $fieldValue = $programTemplate[$key];
                if( !$fieldValue )  {
                    $programTemplate[$key] = ProgramTemplate::DEFAULT_TEMPLATE[$key];
                }
            }
        }
        return $programTemplate;
    }

    public function getTemplate( Program $program )
    {
        $template = $program->getTemplate();
        if( $template )
        {
            // $template = $this->patchImageFieldsWithDefault($template);
        }
        return $template;
    }
}
