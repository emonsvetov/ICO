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
            'welcome_message' => isset($validated['welcome_message']) && $validated['welcome_message'] == 'null' ? null : $validated['welcome_message'] ?? null,
            'button_color' => isset($validated['button_color']) &&  $validated['button_color'] == 'null' ? null : $validated['button_color'] ?? null,
            'button_bg_color' => isset($validated['button_bg_color']) &&  $validated['button_bg_color'] == 'null' ? null : $validated['button_bg_color'] ?? null,
            'button_corner' => $validated['button_corner'],
            'font_family' => isset($validated['font_family']) &&  $validated['font_family'] == 'null' ? null : $validated['font_family'] ?? null,
            'name' => $validated['name'],
        ];
        $newProgramTemplate = ProgramTemplate::create(
            ['program_id' => $program->id] + $fieldsToCreate
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
        $resetTheme = $request->get('resetTheme');
        if ($resetTheme != 'false'){
            foreach($programTemplate->getAttributes() as $key=> $item){
                if (!in_array($key, ['id', 'name', 'created_at', 'updated_at', 'program_id', 'is_active', 'update_id','updated_by'])){
                    $fieldsToUpdate[$key] = null;
                }
                if ($key == 'button_corner'){
                    $fieldsToUpdate[$key] = 0;
                }
                if (in_array($key, ProgramTemplate::IMAGE_FIELDS)) {
                    $oldFile = $programTemplate[$key];
                    if( $oldFile )  {
                        Storage::delete( $oldFile );
                    }
                }
            }
        } else {
            $validated = $request->validated();
            $fieldsToUpdate = [
                'welcome_message' => isset($validated['welcome_message']) && $validated['welcome_message'] == 'null' ? null : $validated['welcome_message'] ?? null,
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
        }

        $programTemplate->update($fieldsToUpdate);

        return $programTemplate;
    }


}
